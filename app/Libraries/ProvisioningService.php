<?php

namespace App\Libraries;

use App\Database\Seeds\RolePermissionsSeeder;
use App\Models\Landlord\CompanyModel;
use App\Models\UserModel;
use CodeIgniter\Database\MigrationRunner;
use Config\Database;
use Config\Migrations as MigrationsConfig;
use Throwable;

/**
 * Provisions a brand-new tenant: creates its database, runs the existing
 * App-namespace migrations against it, seeds a default admin user and
 * baseline role permissions, then registers it in the landlord DB.
 *
 * On any failure past database creation, the newly created database is
 * dropped so no half-provisioned tenant is left behind.
 */
class ProvisioningService
{
    /**
     * @return array The newly created company row (landlord DB).
     *
     * @throws ProvisioningException
     */
    public function provision(
        string $name,
        string $requestedSlug,
        string $adminUsername,
        string $adminPassword,
        string $adminFullName,
        string $plan,
    ): array {
        $slug = strtolower(trim($requestedSlug));

        if (! preg_match('/^[a-z0-9_]{2,40}$/', $slug)) {
            throw new ProvisioningException(
                'Company code must be 2-40 characters: lowercase letters, numbers, and underscores only.',
            );
        }

        if (! SubscriptionPlans::exists($plan)) {
            throw new ProvisioningException('Invalid subscription plan.');
        }

        $companyModel = new CompanyModel();

        if ($companyModel->findBySlug($slug) !== null) {
            throw new ProvisioningException("Company code '{$slug}' is already taken.");
        }

        $dbName = config(Database::class)->tenantDbPrefix . $slug;

        if (strlen($dbName) > 64) {
            throw new ProvisioningException('Company code is too long.');
        }

        // Shared app-level MySQL credential (same one used for the
        // `landlord` group) — see plan §"DB user strategy" for why a
        // per-tenant MySQL user isn't used here.
        $landlordDbConfig = config(Database::class)->landlord;
        $landlordConn     = db_connect('landlord');

        // Some hosts (typical cPanel shared hosting) never grant the
        // app's own DB user CREATE DATABASE — only the control panel
        // can create new databases there. If that's the case, fall back
        // to expecting the superadmin has already created `$dbName`
        // manually (e.g. cPanel > MySQL Databases) and granted this DB
        // user full privileges on it, and proceed using that instead of
        // hard-failing. We only track/drop the database on later
        // failure if *this request* is the one that created it — a
        // manually pre-created database is never auto-deleted.
        $weCreatedDatabase = false;

        try {
            $landlordConn->query('CREATE DATABASE `' . $dbName . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci');
            $weCreatedDatabase = true;
        } catch (Throwable $e) {
            if (! $this->databaseExists($landlordConn, $dbName)) {
                throw new ProvisioningException(
                    "Could not create the tenant database automatically ({$e->getMessage()}). "
                    . "If your hosting doesn't allow this, create a database named '{$dbName}' "
                    . 'manually first (e.g. cPanel > MySQL Databases), grant this app\'s database '
                    . 'user full privileges on it, then submit this form again with the same company code.',
                );
            }
            // else: database already exists and this DB user can see it
            // (i.e. was pre-created + granted) — proceed as if we'd just
            // created it.
        }

        try {
            $tenantConnArray = [
                'hostname' => $landlordDbConfig['hostname'],
                'database' => $dbName,
                'username' => $landlordDbConfig['username'],
                'password' => $landlordDbConfig['password'],
                'DBDriver' => $landlordDbConfig['DBDriver'],
                'port'     => $landlordDbConfig['port'],
                'charset'  => $landlordDbConfig['charset'],
                'DBCollat' => $landlordDbConfig['DBCollat'],
            ];
            $tenantConn = Database::connect($tenantConnArray, false);

            // Reuse the existing 31 App-namespace migrations as-is — no
            // schema duplication. setNamespace('App') deliberately excludes
            // the Landlord-namespace migrations (companies/superadmins),
            // which must never run against a tenant DB.
            $runner = new MigrationRunner(config(MigrationsConfig::class), $tenantConn);
            $runner->setNamespace('App');

            if (! $runner->latest()) {
                throw new ProvisioningException('Migrations did not run successfully against the new tenant database.');
            }

            $userModel = new UserModel($tenantConn);
            $adminId   = $userModel->insert([
                'username'              => $adminUsername,
                'password'              => $adminPassword,
                'full_name'             => $adminFullName,
                'role'                  => 'admin',
                'status'                => 'active',
                // The superadmin chose this password on the admin's
                // behalf, so it's inherently temporary until they set
                // their own.
                'must_change_password'  => 1,
            ]);

            if (! $adminId) {
                $errors = implode(' ', $userModel->errors() ?: ['Unknown validation error.']);
                throw new ProvisioningException("Could not create the company admin user: {$errors}");
            }

            // Baseline permissions so manager/staff/employee checks don't
            // silently fail on a fresh tenant.
            (new RolePermissionsSeeder(config(Database::class), $tenantConn))->run();

            // Branding defaults, seeded with the real company name rather
            // than the generic seeder default.
            $now = date('Y-m-d H:i:s');
            $tenantConn->table('settings')->insertBatch([
                ['setting_key' => 'company_name', 'setting_value' => $name, 'created_at' => $now, 'updated_at' => $now],
                ['setting_key' => 'company_tagline', 'setting_value' => 'Payroll Management System', 'created_at' => $now, 'updated_at' => $now],
                ['setting_key' => 'sidebar_bg', 'setting_value' => '#1e293b', 'created_at' => $now, 'updated_at' => $now],
                ['setting_key' => 'sidebar_text', 'setting_value' => '#94a3b8', 'created_at' => $now, 'updated_at' => $now],
                ['setting_key' => 'accent_color', 'setting_value' => '#2563eb', 'created_at' => $now, 'updated_at' => $now],
                ['setting_key' => 'topbar_bg', 'setting_value' => '#ffffff', 'created_at' => $now, 'updated_at' => $now],
            ]);
        } catch (Throwable $e) {
            if ($weCreatedDatabase) {
                $this->dropDatabase($landlordConn, $dbName);
            }

            throw $e instanceof ProvisioningException
                ? $e
                : new ProvisioningException('Provisioning failed: ' . $e->getMessage());
        }

        $trialEndsAt = date('Y-m-d H:i:s', strtotime('+14 days'));

        $companyId = $companyModel->insert([
            'name'                    => $name,
            'slug'                    => $slug,
            'db_host'                 => $landlordDbConfig['hostname'],
            'db_name'                 => $dbName,
            'db_username'             => $landlordDbConfig['username'],
            'db_password'             => CredentialCipher::encrypt($landlordDbConfig['password']),
            'status'                  => 'trial',
            'subscription_plan'       => $plan,
            'trial_ends_at'           => $trialEndsAt,
            'subscription_expires_at' => $trialEndsAt,
        ]);

        if (! $companyId) {
            if ($weCreatedDatabase) {
                $this->dropDatabase($landlordConn, $dbName);
            }
            $errors = implode(' ', $companyModel->errors() ?: ['Unknown validation error.']);
            throw new ProvisioningException("Could not save the company record: {$errors}");
        }

        return $companyModel->find($companyId);
    }

    private function dropDatabase($landlordConn, string $dbName): void
    {
        try {
            $landlordConn->query('DROP DATABASE IF EXISTS `' . $dbName . '`');
        } catch (Throwable) {
            // Best-effort cleanup only — the original exception is what surfaces.
        }
    }

    /**
     * Whether $dbName exists and is visible to the connecting DB user
     * (MySQL only lists schemas the user has some privilege on, which is
     * exactly what we need here: "does it exist AND can we use it").
     */
    private function databaseExists($landlordConn, string $dbName): bool
    {
        try {
            $result = $landlordConn->query(
                'SELECT SCHEMA_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = ?',
                [$dbName],
            );

            return $result !== false && $result->getNumRows() > 0;
        } catch (Throwable) {
            return false;
        }
    }
}

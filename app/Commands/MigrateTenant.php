<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use CodeIgniter\Database\MigrationRunner;
use Config\Database;
use Config\Migrations as MigrationsConfig;

/**
 * Runs the App-namespace migrations against a single, already-existing
 * tenant database by name — the same mechanism ProvisioningService uses
 * for brand-new tenants, exposed here so an existing tenant can be
 * brought up to date after a schema change (e.g. a migration added after
 * that tenant was originally provisioned).
 *
 * Usage: php spark migrate:tenant payroll_<slug>
 */
class MigrateTenant extends BaseCommand
{
    protected $group       = 'Database';
    protected $name        = 'migrate:tenant';
    protected $description = 'Runs App-namespace migrations against a single tenant database by name.';
    protected $usage       = 'migrate:tenant <db_name>';

    public function run(array $params)
    {
        $dbName = $params[0] ?? null;

        if (! $dbName) {
            CLI::error('Usage: php spark migrate:tenant <db_name>');
            return;
        }

        $template = config(Database::class)->landlord;

        $conn = Database::connect([
            'hostname' => $template['hostname'],
            'database' => $dbName,
            'username' => $template['username'],
            'password' => $template['password'],
            'DBDriver' => $template['DBDriver'],
            'port'     => $template['port'],
            'charset'  => $template['charset'],
            'DBCollat' => $template['DBCollat'],
        ], false);

        $runner = new MigrationRunner(config(MigrationsConfig::class), $conn);
        $runner->setNamespace('App');

        if (! $runner->latest()) {
            CLI::error("Migrations failed against '{$dbName}'.");
            return;
        }

        foreach ($runner->getCliMessages() as $message) {
            CLI::write($message);
        }

        CLI::write("Tenant '{$dbName}' migrated.", 'green');
    }
}

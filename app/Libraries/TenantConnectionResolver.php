<?php

namespace App\Libraries;

use Config\Database;

/**
 * Points the shared `default` DB connection group at a given tenant's
 * database for the remainder of the current request. Every existing
 * Model in the app uses `default` implicitly, so this is the single
 * lever that makes per-tenant DB switching work without touching any
 * of the existing Model classes.
 */
class TenantConnectionResolver
{
    /**
     * Mutate Config\Database::$default in place so subsequent
     * db_connect(null) calls (i.e. every existing Model) resolve
     * to this tenant's database.
     *
     * @param array $company A company row with db_host/db_name/db_username/db_password
     *                       (db_password already decrypted).
     */
    public static function applyToDefaultGroup(array $company): void
    {
        $dbConfig = config(Database::class);

        $dbConfig->default['hostname'] = $company['db_host'];
        $dbConfig->default['database'] = $company['db_name'];
        $dbConfig->default['username'] = $company['db_username'];
        $dbConfig->default['password'] = $company['db_password'];
    }

    /**
     * Build a standalone connection config array for a tenant, for use
     * where an isolated (non-shared) connection object is wanted instead
     * of mutating the `default` group — e.g. during provisioning.
     *
     * @param array $company A company row with db_password already decrypted.
     */
    public static function connectionArrayFor(array $company): array
    {
        $dbConfig = config(Database::class);
        $template = $dbConfig->default;

        $template['hostname'] = $company['db_host'];
        $template['database'] = $company['db_name'];
        $template['username'] = $company['db_username'];
        $template['password'] = $company['db_password'];

        return $template;
    }
}

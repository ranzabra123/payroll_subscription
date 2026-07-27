<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use CodeIgniter\Database\MigrationRunner;
use Config\Migrations as MigrationsConfig;

/**
 * One-off helper: runs the Landlord-namespace migrations against the
 * actual `landlord` DB connection. `spark migrate -g <group>` only tags
 * bookkeeping rows with that group name — it does NOT reconnect the
 * runner to that group's database (MigrationRunner opens its connection
 * in the constructor using the default group, before -g is even parsed).
 * Passing the group name into the constructor is the only way to get a
 * runner actually bound to a non-default connection.
 */
class MigrateLandlord extends BaseCommand
{
    protected $group       = 'Database';
    protected $name        = 'migrate:landlord';
    protected $description = 'Runs Landlord-namespace migrations against the landlord DB connection.';

    public function run(array $params)
    {
        $runner = new MigrationRunner(config(MigrationsConfig::class), 'landlord');
        $runner->setNamespace('Landlord');

        if (! $runner->latest('landlord')) {
            CLI::error('Migration failed.');
            return;
        }

        foreach ($runner->getCliMessages() as $message) {
            CLI::write($message);
        }

        CLI::write('Landlord migrations complete.', 'green');
    }
}

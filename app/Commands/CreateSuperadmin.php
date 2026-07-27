<?php

namespace App\Commands;

use App\Models\Landlord\SuperadminModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Creates (or resets the password of) a superadmin account.
 *
 * Usage: php spark superadmin:create <username> <password> "<Full Name>"
 */
class CreateSuperadmin extends BaseCommand
{
    protected $group       = 'Database';
    protected $name        = 'superadmin:create';
    protected $description = 'Creates a superadmin account for the platform console.';
    protected $usage       = 'superadmin:create <username> <password> <full_name>';

    public function run(array $params)
    {
        [$username, $password, $fullName] = $params + [null, null, null];

        if (! $username || ! $password || ! $fullName) {
            CLI::error('Usage: php spark superadmin:create <username> <password> "<Full Name>"');
            return;
        }

        if (strlen($password) < 6) {
            CLI::error('Password must be at least 6 characters.');
            return;
        }

        $model = new SuperadminModel();
        $existing = $model->where('username', $username)->first();

        if ($existing) {
            $model->update($existing['id'], ['password' => $password]);
            CLI::write("Superadmin '{$username}' already existed — password updated.", 'yellow');
            return;
        }

        $id = $model->insert([
            'username'  => $username,
            'password'  => $password,
            'full_name' => $fullName,
            'status'    => 'active',
        ]);

        if (! $id) {
            CLI::error(implode(' ', $model->errors() ?: ['Unknown validation error.']));
            return;
        }

        CLI::write("Superadmin '{$username}' created.", 'green');
    }
}

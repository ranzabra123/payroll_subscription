<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Tracks whether a user must change their password before doing anything
 * else — set when a superadmin (provisioning a company, or resetting
 * credentials) assigns a password on someone else's behalf, since that
 * password is inherently temporary until the real user picks their own.
 */
class AddMustChangePasswordToUsers extends Migration
{
    public function up(): void
    {
        if (! $this->db->fieldExists('must_change_password', 'users')) {
            $this->forge->addColumn('users', [
                'must_change_password' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'null'       => false,
                    'default'    => 0,
                    'after'      => 'password',
                ],
            ]);
        }
    }

    public function down(): void
    {
        if ($this->db->fieldExists('must_change_password', 'users')) {
            $this->forge->dropColumn('users', 'must_change_password');
        }
    }
}

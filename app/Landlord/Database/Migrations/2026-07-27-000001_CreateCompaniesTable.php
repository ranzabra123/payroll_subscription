<?php

namespace Landlord\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCompaniesTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 10,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
            ],
            'slug' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'unique'     => true,
            ],
            'db_host' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'db_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
            ],
            'db_username' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'db_password' => [
                'type' => 'TEXT',
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['trial', 'active', 'suspended', 'cancelled'],
                'default'    => 'trial',
            ],
            'subscription_plan' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
                'default'    => null,
            ],
            'trial_ends_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'default' => null,
            ],
            'subscription_expires_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'default' => null,
            ],
            'contact_email' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
                'default'    => null,
            ],
            'created_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'default' => null,
            ],
            'updated_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'default' => null,
            ],
            'deleted_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'default' => null,
            ],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('companies');
    }

    public function down(): void
    {
        $this->forge->dropTable('companies');
    }
}

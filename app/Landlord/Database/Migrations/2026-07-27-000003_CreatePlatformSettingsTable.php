<?php

namespace Landlord\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Platform-wide branding shown before any tenant/company is known — the
 * shared login page, the superadmin console, and the browser tab favicon
 * everywhere on those pages. Distinct from the per-tenant `settings`
 * table (which lives inside each tenant's own database).
 */
class CreatePlatformSettingsTable extends Migration
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
            'setting_key' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'unique'     => true,
            ],
            'setting_value' => [
                'type' => 'TEXT',
                'null' => true,
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
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('platform_settings');
    }

    public function down(): void
    {
        $this->forge->dropTable('platform_settings');
    }
}

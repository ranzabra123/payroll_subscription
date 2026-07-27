<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * `username`, `summary`, and `url` exist on the production `audit_logs`
 * table (and are actively written by AuditLogModel::logAction()) but were
 * never captured in a migration — they must have been added directly to
 * the live database at some point. Any freshly migrated tenant database
 * was missing them, which broke the very first login for a newly
 * provisioned company. Guarded with fieldExists() so this is a no-op
 * against the existing production database (which already has them) and
 * only actually adds the columns for tenants that don't.
 */
class AddMissingColumnsToAuditLogs extends Migration
{
    public function up(): void
    {
        if (! $this->db->fieldExists('username', 'audit_logs')) {
            $this->forge->addColumn('audit_logs', [
                'username' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                    'null'       => true,
                    'default'    => null,
                    'after'      => 'user_id',
                ],
            ]);
        }

        if (! $this->db->fieldExists('summary', 'audit_logs')) {
            $this->forge->addColumn('audit_logs', [
                'summary' => [
                    'type'    => 'TEXT',
                    'null'    => true,
                    'default' => null,
                    'after'   => 'new_values',
                ],
            ]);
        }

        if (! $this->db->fieldExists('url', 'audit_logs')) {
            $this->forge->addColumn('audit_logs', [
                'url' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 500,
                    'null'       => true,
                    'default'    => null,
                    'after'      => 'ip_address',
                ],
            ]);
        }
    }

    public function down(): void
    {
        foreach (['username', 'summary', 'url'] as $column) {
            if ($this->db->fieldExists($column, 'audit_logs')) {
                $this->forge->dropColumn('audit_logs', $column);
            }
        }
    }
}

<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * `pharmacy_deduction` exists on the production `payroll_details` table
 * but was never captured in a migration. See
 * 2026-07-27-000003_AddMissingColumnsToAuditLogs for the same drift
 * pattern and why the fieldExists() guard is needed here too.
 */
class AddPharmacyDeductionToPayrollDetails extends Migration
{
    public function up(): void
    {
        if (! $this->db->fieldExists('pharmacy_deduction', 'payroll_details')) {
            $this->forge->addColumn('payroll_details', [
                'pharmacy_deduction' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '10,2',
                    'null'       => false,
                    'default'    => 0.00,
                    'after'      => 'absent_deduction',
                ],
            ]);
        }
    }

    public function down(): void
    {
        if ($this->db->fieldExists('pharmacy_deduction', 'payroll_details')) {
            $this->forge->dropColumn('payroll_details', 'pharmacy_deduction');
        }
    }
}

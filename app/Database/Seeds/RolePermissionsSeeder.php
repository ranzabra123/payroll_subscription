<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\Seeder;
use Config\Database;

class RolePermissionsSeeder extends Seeder
{
    /**
     * CI4's own Seeder::__construct() always builds $this->forge from
     * Database::forge($this->DBGroup) — using the group *name* (null
     * here, since this seeder is deliberately tenant-agnostic), not the
     * $db connection actually passed in. That eagerly opens a real
     * connection to the `default` group just to build a Forge instance
     * this seeder never uses (run() only ever touches $this->db) —
     * harmless in local dev where `default` usually happens to be some
     * other reachable database, but a hard crash in production where
     * `default` is intentionally left unconfigured. Overridden here to
     * skip that unused, unsafe eager connection entirely.
     */
    public function __construct(Database $config, ?BaseConnection $db = null)
    {
        $this->seedPath = rtrim($config->filesPath ?? APPPATH . 'Database/', '\\/') . '/Seeds/';
        $this->config   = &$config;
        $this->db       = $db ?? Database::connect($this->DBGroup);
    }

    public function run()
    {
        $defaults = [
            // Managers can view, add, edit most things; no delete
            'manager' => [
                'dashboard'   => [1, 0, 0, 0],
                'employees'   => [1, 1, 1, 0],
                'attendance'  => [1, 1, 1, 0],
                'payroll'     => [1, 1, 1, 0],
                'deductions'  => [1, 1, 1, 0],
                'benefits'    => [1, 1, 1, 0],
                'special_days'=> [1, 1, 1, 0],
                'reports'     => [1, 0, 0, 0],
            ],
            // Staff can only view attendance and dashboard
            'staff' => [
                'dashboard'   => [1, 0, 0, 0],
                'employees'   => [1, 0, 0, 0],
                'attendance'  => [1, 1, 0, 0],
                'payroll'     => [0, 0, 0, 0],
                'deductions'  => [0, 0, 0, 0],
                'benefits'    => [0, 0, 0, 0],
                'special_days'=> [0, 0, 0, 0],
                'reports'     => [0, 0, 0, 0],
            ],
            // Employee can only view their own dashboard and payroll info
            'employee' => [
                'dashboard'   => [1, 0, 0, 0],
                'employees'   => [0, 0, 0, 0],
                'attendance'  => [1, 0, 0, 0],
                'payroll'     => [1, 0, 0, 0],
                'deductions'  => [1, 0, 0, 0],
                'benefits'    => [1, 0, 0, 0],
                'special_days'=> [0, 0, 0, 0],
                'reports'     => [0, 0, 0, 0],
            ],
        ];

        foreach ($defaults as $role => $modules) {
            foreach ($modules as $module => [$view, $add, $edit, $delete]) {
                $this->db->table('role_permissions')->replace([
                    'role'       => $role,
                    'module'     => $module,
                    'can_view'   => $view,
                    'can_add'    => $add,
                    'can_edit'   => $edit,
                    'can_delete' => $delete,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            }
        }

        echo "Role permissions seeded.\n";
    }
}

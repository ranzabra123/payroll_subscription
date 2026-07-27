<?php

namespace Tests\Unit;

use App\Database\Seeds\RolePermissionsSeeder;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;

/**
 * Regression test for a bug found while building tenant provisioning:
 * RolePermissionsSeeder::run() used to call \Config\Database::connect()
 * directly instead of $this->db, so it always wrote to the *default*
 * connection regardless of which connection was injected into it — which
 * meant seeding role permissions into a freshly provisioned tenant
 * database (a connection object, not a named group) silently did nothing
 * to that tenant and instead polluted whatever `default` happened to be.
 *
 * This creates its own disposable scratch database (independent of
 * `default`/`landlord`), runs the seeder against a connection pointed at
 * it, and confirms the rows actually landed there — which is only
 * possible if the seeder honored the injected connection.
 *
 * Requires a reachable MySQL server with CREATE DATABASE privileges
 * (same server used by the `landlord` group) — skips itself otherwise.
 *
 * @internal
 */
final class RolePermissionsSeederConnectionTest extends CIUnitTestCase
{
    private const SCRATCH_DB = 'payroll_test_scratch_rp_seeder';

    protected function setUp(): void
    {
        parent::setUp();

        try {
            db_connect('landlord')->query('CREATE DATABASE IF NOT EXISTS `' . self::SCRATCH_DB . '`');
        } catch (\Throwable $e) {
            $this->markTestSkipped('No reachable MySQL server for scratch DB: ' . $e->getMessage());
        }
    }

    protected function tearDown(): void
    {
        try {
            db_connect('landlord')->query('DROP DATABASE IF EXISTS `' . self::SCRATCH_DB . '`');
        } catch (\Throwable) {
            // best-effort cleanup
        }

        parent::tearDown();
    }

    public function testSeederWritesToTheInjectedConnectionNotTheDefaultOne(): void
    {
        $template = config(Database::class)->landlord;

        $scratchConn = Database::connect([
            'hostname' => $template['hostname'],
            'database' => self::SCRATCH_DB,
            'username' => $template['username'],
            'password' => $template['password'],
            'DBDriver' => $template['DBDriver'],
            'port'     => $template['port'],
            'charset'  => $template['charset'],
            'DBCollat' => $template['DBCollat'],
        ], false);

        $forge = Database::forge($scratchConn);

        $forge->addField([
            'id'         => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'role'       => ['type' => 'VARCHAR', 'constraint' => 20],
            'module'     => ['type' => 'VARCHAR', 'constraint' => 60],
            'can_view'   => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'can_add'    => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'can_edit'   => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'can_delete' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $forge->addPrimaryKey('id');
        $forge->createTable('role_permissions', true);

        // The seeder echoes a CLI progress line by design (used by
        // `php spark db:seed`) — expect it rather than let PHPUnit's
        // strict-output check flag it as risky.
        $this->expectOutputString("Role permissions seeded.\n");

        (new RolePermissionsSeeder(config(Database::class), $scratchConn))->run();

        $count = $scratchConn->table('role_permissions')->countAllResults();

        $this->assertGreaterThan(
            0,
            $count,
            'RolePermissionsSeeder must write into the connection it was constructed with.',
        );
        // 3 roles (manager/staff/employee) x 8 modules each.
        $this->assertSame(24, $count);
    }
}

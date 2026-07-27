<?php

namespace Tests\Unit;

use App\Models\UserModel;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Regression test for a pre-existing bug found while wiring up
 * multi-tenant provisioning: the DB enum and RolePermissionModel both
 * already treat 'employee' as a valid user role, but UserModel's own
 * validation rule silently rejected it, so an 'employee'-role user
 * could never actually be created through the UI.
 *
 * @internal
 */
final class UserModelValidationTest extends CIUnitTestCase
{
    private function roleRule(): string
    {
        $model = new UserModel();

        $ref  = new \ReflectionProperty(UserModel::class, 'validationRules');
        $ref->setAccessible(true);
        $rules = $ref->getValue($model);

        return $rules['role'];
    }

    public function testRoleValidationAcceptsAllFourRoles(): void
    {
        $rule = $this->roleRule();

        foreach (['admin', 'manager', 'staff', 'employee'] as $role) {
            $this->assertStringContainsString($role, $rule, "role rule should allow '{$role}'");
        }
    }

    public function testRoleValidationIsStillRequired(): void
    {
        $this->assertStringContainsString('required', $this->roleRule());
    }
}

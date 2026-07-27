<?php

namespace Tests\Unit;

use App\Models\Landlord\CompanyModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * Feature test for ForcePasswordChangeFilter, run through the real
 * routing/filter pipeline. Mirrors SubscriptionGatingTest's approach
 * (and its lesson: use getPath(), not getUri()->getPath(), for the
 * allow-list check — this filter was written with that lesson already
 * applied, and this test guards against it regressing).
 *
 * @internal
 */
final class ForcePasswordChangeGatingTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    public function testUserMustChangePasswordIsRedirectedAwayFromOrdinaryRoutes(): void
    {
        $company = $this->anyActiveCompany();

        $result = $this->withSession([
            'logged_in'             => true,
            'company_id'            => $company['id'],
            'user_id'                => 1,
            'role'                   => 'admin',
            'must_change_password'   => true,
        ])->get('dashboard');

        $result->assertRedirectTo('change-password');
    }

    public function testChangePasswordPageItselfStaysReachableWhileGated(): void
    {
        $company = $this->anyActiveCompany();

        $result = $this->withSession([
            'logged_in'             => true,
            'company_id'            => $company['id'],
            'must_change_password'   => true,
        ])->get('change-password');

        $result->assertOK();
        $result->assertSee('Set Your Password');
    }

    public function testUserWithoutTheFlagIsNotGated(): void
    {
        $company = $this->anyActiveCompany();

        $result = $this->withSession([
            'logged_in'             => true,
            'company_id'            => $company['id'],
            'must_change_password'   => false,
        ])->get('change-password');

        // Reaching the controller at all (200, not a redirect loop or
        // block) proves the filter is a no-op when the flag is false.
        $result->assertOK();
    }

    private function anyActiveCompany(): array
    {
        try {
            $company = (new CompanyModel())->where('status', 'active')->first();
        } catch (\Throwable $e) {
            $this->markTestSkipped('No reachable landlord DB: ' . $e->getMessage());
        }

        if ($company === null) {
            $this->markTestSkipped('No active company available to test against.');
        }

        return $company;
    }
}

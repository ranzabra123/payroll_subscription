<?php

namespace Tests\Unit;

use App\Models\Landlord\CompanyModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * Feature test for TenantResolverFilter's subscription gating, run
 * through the real routing/filter pipeline (not just isBlocked() in
 * isolation — see CompanyModelTest for that) so it also catches
 * filter-plumbing bugs, not just business-logic ones.
 *
 * This is a real regression test for a bug found while building this:
 * the filter originally read the request path via getUri()->getPath(),
 * which (when the app is served from a subfolder) includes the
 * subfolder prefix and never equals the bare 'subscription/expired'
 * allow-listed path — so a blocked tenant hitting the expired page
 * itself got redirected to itself in an infinite loop, and every
 * other route redirected there as expected but the "safe" page was
 * unreachable. Fixed by switching to getPath(), which is CI4's
 * already-app-relative routed path. This test would fail against the
 * old getUri()->getPath() code.
 *
 * Uses a real, temporary row in the landlord DB (cleaned up in
 * tearDown) since CompanyModel's $DBGroup = 'landlord' is a real MySQL
 * group, not affected by the SQLite-less `tests` group substitution.
 *
 * @internal
 */
final class SubscriptionGatingTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    private ?int $companyId = null;

    protected function setUp(): void
    {
        parent::setUp();

        try {
            $model = new CompanyModel();
            $id    = $model->insert([
                'name'                    => 'Gating Test Co',
                'slug'                    => 'gatingtestco',
                'db_host'                 => 'localhost',
                'db_name'                 => 'payroll_gatingtestco_unused',
                'db_username'             => 'unused',
                'db_password'             => 'unused',
                'status'                  => 'suspended',
                'subscription_expires_at' => date('Y-m-d H:i:s', strtotime('+1 year')),
            ]);

            if ($id === false) {
                $this->markTestSkipped('Could not insert test company: ' . implode(' ', $model->errors() ?: []));
            }

            $this->companyId = $id;
        } catch (\Throwable $e) {
            $this->markTestSkipped('No reachable landlord DB: ' . $e->getMessage());
        }
    }

    protected function tearDown(): void
    {
        if ($this->companyId !== null) {
            (new CompanyModel())->delete($this->companyId, true);
        }

        parent::tearDown();
    }

    public function testBlockedTenantIsRedirectedAwayFromOrdinaryRoutes(): void
    {
        $result = $this->withSession([
            'logged_in' => true,
            'company_id' => $this->companyId,
            'user_id'    => 1,
            'role'       => 'admin',
        ])->get('dashboard');

        $result->assertRedirectTo('subscription/expired');
    }

    public function testSubscriptionExpiredPageItselfStaysReachableWhileBlocked(): void
    {
        $result = $this->withSession([
            'logged_in' => true,
            'company_id' => $this->companyId,
        ])->get('subscription/expired');

        $result->assertOK();
        $result->assertSee('Subscription Inactive');
    }

    public function testLogoutStaysReachableWhileBlocked(): void
    {
        $result = $this->withSession([
            'logged_in' => true,
            'company_id' => $this->companyId,
        ])->get('logout');

        $result->assertRedirectTo('login');
    }
}

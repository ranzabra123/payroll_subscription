<?php

namespace Tests\Unit;

use App\Models\Landlord\CompanyModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * Feature test for the interactive product tour, run through the real
 * routing/filter/view pipeline (like ForcePasswordChangeGatingTest) so
 * it catches actual rendering errors — an undefined helper, a missing
 * variable in dashboard/index.php, a malformed layout edit — not just
 * unit-level logic.
 *
 * @internal
 */
final class ProductTourTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    public function testDashboardRendersTourTriggerAndDataAttributes(): void
    {
        $company = $this->anyActiveCompany();

        $result = $this->withSession([
            'logged_in'  => true,
            'company_id' => $company['id'],
            'user_id'    => 1,
            'full_name'  => 'Test User',
            'role'       => 'admin',
        ])->get('dashboard');

        $result->assertOK();
        $body = $result->response()->getBody();
        $this->assertStringContainsString('id="tour-start-btn"', $body);
        $this->assertStringContainsString('data-tour-page="dashboard"', $body);
        $this->assertStringContainsString('data-tour="stat-cards"', $body);
        $this->assertStringContainsString('data-tour="first-task-add-employee"', $body);
        // Admin-only markup (Advanced Features step's target).
        $this->assertStringContainsString('data-tour="advanced-features"', $body);
        $this->assertStringContainsString('data-tour="nav-employees"', $body);
    }

    public function testTourAssetsAreLinkedFromTheLayout(): void
    {
        $company = $this->anyActiveCompany();

        $result = $this->withSession([
            'logged_in'  => true,
            'company_id' => $company['id'],
            'user_id'    => 1,
            'role'       => 'admin',
        ])->get('dashboard');

        $body = $result->response()->getBody();
        $this->assertStringContainsString('assets/css/tour.css', $body);
        $this->assertStringContainsString('assets/js/tour.js', $body);
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

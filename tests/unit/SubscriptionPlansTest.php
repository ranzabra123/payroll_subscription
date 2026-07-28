<?php

namespace Tests\Unit;

use App\Libraries\SubscriptionPlans;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class SubscriptionPlansTest extends CIUnitTestCase
{
    public function testStarterPlanCaps(): void
    {
        $this->assertSame(50, SubscriptionPlans::maxEmployees('starter'));
        $this->assertSame(1, SubscriptionPlans::maxBranches('starter'));
    }

    public function testBusinessPlanCaps(): void
    {
        $this->assertSame(200, SubscriptionPlans::maxEmployees('business'));
        $this->assertSame(5, SubscriptionPlans::maxBranches('business'));
    }

    public function testEnterprisePlanIsUnlimited(): void
    {
        $this->assertNull(SubscriptionPlans::maxEmployees('enterprise'));
        $this->assertNull(SubscriptionPlans::maxBranches('enterprise'));
    }

    public function testUnknownOrMissingPlanIsTreatedAsUnlimited(): void
    {
        // A company with no plan assigned yet (legacy row, or superadmin
        // hasn't set one) must never be silently blocked from working.
        $this->assertNull(SubscriptionPlans::maxEmployees(null));
        $this->assertNull(SubscriptionPlans::maxBranches(null));
        $this->assertNull(SubscriptionPlans::maxEmployees('bogus'));
        $this->assertNull(SubscriptionPlans::maxBranches('bogus'));
    }
}

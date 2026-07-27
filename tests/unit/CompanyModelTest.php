<?php

namespace Tests\Unit;

use App\Models\Landlord\CompanyModel;
use CodeIgniter\Test\CIUnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * @internal
 */
final class CompanyModelTest extends CIUnitTestCase
{
    private function company(array $overrides = []): array
    {
        return array_merge([
            'status'                   => 'active',
            'subscription_expires_at'  => null,
        ], $overrides);
    }

    public function testActiveCompanyWithNoExpiryIsNotBlocked(): void
    {
        $model = new CompanyModel();
        $this->assertFalse($model->isBlocked($this->company()));
    }

    public function testActiveCompanyWithFutureExpiryIsNotBlocked(): void
    {
        $model = new CompanyModel();
        $company = $this->company(['subscription_expires_at' => date('Y-m-d H:i:s', strtotime('+1 day'))]);
        $this->assertFalse($model->isBlocked($company));
    }

    public function testActiveCompanyPastExpiryIsBlocked(): void
    {
        $model = new CompanyModel();
        $company = $this->company(['subscription_expires_at' => date('Y-m-d H:i:s', strtotime('-1 day'))]);
        $this->assertTrue($model->isBlocked($company));
    }

    public function testSuspendedCompanyIsBlockedRegardlessOfExpiry(): void
    {
        $model = new CompanyModel();
        $company = $this->company([
            'status'                  => 'suspended',
            'subscription_expires_at' => date('Y-m-d H:i:s', strtotime('+1 year')),
        ]);
        $this->assertTrue($model->isBlocked($company));
    }

    public function testCancelledCompanyIsBlocked(): void
    {
        $model = new CompanyModel();
        $this->assertTrue($model->isBlocked($this->company(['status' => 'cancelled'])));
    }

    public function testTrialCompanyWithFutureExpiryIsNotBlocked(): void
    {
        $model = new CompanyModel();
        $company = $this->company([
            'status'                  => 'trial',
            'subscription_expires_at' => date('Y-m-d H:i:s', strtotime('+13 days')),
        ]);
        $this->assertFalse($model->isBlocked($company));
    }

    /**
     * Confirms CompanyModel's own validation rule contains exactly the
     * pattern ProvisioningService independently re-validates against
     * (the sole defense against SQL injection via database identifier —
     * see ProvisioningServiceValidationTest). If either place drifts
     * from the other, this test (or that one) should fail.
     */
    public function testSlugValidationRuleContainsExpectedPattern(): void
    {
        $model = new CompanyModel();

        $ref = new \ReflectionProperty(CompanyModel::class, 'validationRules');
        $ref->setAccessible(true);
        $rules = $ref->getValue($model);

        $this->assertStringContainsString('regex_match[/^[a-z0-9_]{2,40}$/]', $rules['slug']);
    }

    #[DataProvider('slugProvider')]
    public function testSlugPatternAcceptsOrRejectsAsExpected(string $slug, bool $shouldMatch): void
    {
        // Mirrors the literal pattern asserted above — kept as a plain
        // string here (not extracted from the rule) so a malformed rule
        // string can't also corrupt this test's own regex.
        $pattern = '/^[a-z0-9_]{2,40}$/';

        $this->assertSame($shouldMatch, (bool) preg_match($pattern, $slug), "slug '{$slug}'");
    }

    public static function slugProvider(): array
    {
        return [
            'valid lowercase'        => ['acme', true],
            'valid with digits'      => ['acme123', true],
            'valid with underscore'  => ['acme_corp', true],
            'too short'              => ['a', false],
            'uppercase rejected'     => ['Acme', false],
            'dash rejected'          => ['acme-corp', false],
            'space rejected'         => ['acme corp', false],
            'empty rejected'         => ['', false],
        ];
    }
}

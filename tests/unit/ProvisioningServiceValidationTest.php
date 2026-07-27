<?php

namespace Tests\Unit;

use App\Libraries\ProvisioningException;
use App\Libraries\ProvisioningService;
use CodeIgniter\Test\CIUnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * These cover only the fast-fail slug-validation path of
 * ProvisioningService::provision(), which is checked before any database
 * is touched — the SQL-injection-via-identifier defense described in the
 * plan (CREATE DATABASE/RENAME TABLE can't use bound parameters, so this
 * allow-list is the entire defense). Full happy-path provisioning (real
 * CREATE DATABASE + migrations + seeding) requires a live MySQL server
 * with CREATE privileges and is exercised as a manual end-to-end check
 * instead, not here.
 *
 * @internal
 */
final class ProvisioningServiceValidationTest extends CIUnitTestCase
{
    #[DataProvider('invalidSlugProvider')]
    public function testRejectsInvalidSlugBeforeTouchingTheDatabase(string $slug): void
    {
        $this->expectException(ProvisioningException::class);
        $this->expectExceptionMessage('Company code must be 2-40 characters');

        (new ProvisioningService())->provision('Some Company', $slug, 'admin', 'password123', 'Admin User', 'starter');
    }

    public static function invalidSlugProvider(): array
    {
        return [
            // Note: uppercase is NOT invalid here — ProvisioningService
            // lowercases the slug before validating it, so 'Acme' is a
            // legitimate input that normalizes to the valid 'acme'.
            'empty'             => [''],
            'too short'         => ['a'],
            'dash not allowed'  => ['acme-corp'],
            'space not allowed' => ['acme corp'],
            'sql metacharacter' => ["acme`; DROP DATABASE payroll; --"],
            'too long'          => [str_repeat('a', 41)],
        ];
    }

    public function testRejectsAnUnknownSubscriptionPlan(): void
    {
        $this->expectException(ProvisioningException::class);
        $this->expectExceptionMessage('Invalid subscription plan');

        (new ProvisioningService())->provision('Some Company', 'somecompany', 'admin', 'password123', 'Admin User', 'gold_tier_9000');
    }
}

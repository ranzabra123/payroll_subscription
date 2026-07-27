<?php

namespace Tests\Unit;

use App\Models\Landlord\SuperadminModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * Regression test for a bug found while adding superadmin account
 * management: SuperadminsController::store()/update() call the
 * Controller-level $this->validate($rules) helper, which has no
 * knowledge of SuperadminModel::$DBGroup — so a 2-part
 * is_unique[superadmins.username] rule resolved against whatever
 * `default` happened to be (a tenant DB), not the `landlord` group
 * that actually holds the superadmins table, causing a 500
 * ("Table '...superadmins' doesn't exist") instead of a normal
 * "username taken" validation error. Fixed with the explicit 3-part
 * is_unique[landlord.superadmins.username] form.
 *
 * Runs through the real routing/filter/controller pipeline (not just
 * the model in isolation) so it also catches this exact class of
 * plumbing bug, not just business-logic bugs.
 *
 * @internal
 */
final class SuperadminAccountManagementTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    public function testCreatingASuperadminWithADuplicateUsernameFailsValidationInsteadOf500(): void
    {
        try {
            $existing = (new SuperadminModel())->first();
        } catch (\Throwable $e) {
            $this->markTestSkipped('No reachable landlord DB: ' . $e->getMessage());
        }

        if ($existing === null) {
            $this->markTestSkipped('No existing superadmin row to collide with.');
        }

        $result = $this->withSession([
            'superadmin_logged_in' => true,
            'superadmin_id'        => $existing['id'],
            'superadmin_username'  => $existing['username'],
        ])->post('superadmin/admins/store', [
            'full_name' => 'Duplicate Test',
            'username'  => $existing['username'], // deliberately colliding
            'password'  => 'somepassword',
            'status'    => 'active',
        ]);

        // Must NOT be a 500 (the bug's symptom) — a redirect back to the
        // form with a validation error is the correct behavior.
        $result->assertStatus(302);
        $result->assertSessionHas('errors');
    }
}

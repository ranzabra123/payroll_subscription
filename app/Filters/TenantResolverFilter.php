<?php

namespace App\Filters;

use App\Libraries\CredentialCipher;
use App\Libraries\TenantConnectionResolver;
use App\Models\Landlord\CompanyModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Resolves the current tenant's database connection for every request.
 *
 * Runs before the 'auth' route-group filter (registered in $required['before']
 * in Config\Filters) so `default` is already pointed at the correct tenant DB
 * by the time any controller/Model runs.
 *
 * The company row is re-fetched from the landlord DB on every request (never
 * trusted from session beyond the id) so a suspended/deleted/tampered company
 * takes effect immediately, not just at next login.
 */
class TenantResolverFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null): mixed
    {
        // Superadmin routes never touch the tenant `default` group.
        if (session()->get('superadmin_logged_in')) {
            return null;
        }

        // No tenant session yet (anonymous visitor, login page) — nothing to resolve.
        if (!session()->get('logged_in') || !session()->get('company_id')) {
            return null;
        }

        $companyModel = new CompanyModel();
        $company      = $companyModel->find(session()->get('company_id'));

        if ($company === null) {
            session()->destroy();

            return redirect()->to(site_url('login'))->with('error', 'Your company account is no longer available.');
        }

        // Subscription status/expiry gating — checked before any tenant data
        // is touched (before the `default` group is even pointed at this
        // tenant's DB), so a blocked company's data is never queried. Session
        // is left intact: reactivating/extending takes effect on the user's
        // very next request, no re-login needed. `logout` stays reachable so
        // a blocked user isn't stuck.
        if ($companyModel->isBlocked($company)) {
            // getPath() (not getUri()->getPath()) — this is CI4's already
            // app-relative routed path, independent of whether the app is
            // served from a vhost root or a subfolder (see DYNAMIC_BASE_URL
            // in Constants.php for the same distinction).
            $path = trim($request->getPath(), '/');

            if (! in_array($path, ['subscription/expired', 'logout'], true)) {
                return redirect()->to(site_url('subscription/expired'));
            }

            return null;
        }

        // Kept fresh every request (not just at login) so a superadmin
        // changing the plan takes effect immediately for plan-limit
        // checks (see SubscriptionPlans / EmployeesController::store() /
        // SettingsController::addBranch()), same reasoning as the status/
        // expiry re-fetch above.
        session()->set('subscription_plan', $company['subscription_plan']);

        $company['db_password'] = CredentialCipher::decrypt($company['db_password']);
        TenantConnectionResolver::applyToDefaultGroup($company);

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null): mixed
    {
        return null;
    }
}

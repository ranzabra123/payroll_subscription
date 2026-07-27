<?php

namespace App\Controllers;

use CodeIgniter\Controller;

/**
 * Shown when TenantResolverFilter blocks a logged-in tenant user because
 * their company is suspended/cancelled/past its subscription expiry.
 * Deliberately does not query the tenant DB (it isn't connected at this
 * point — see TenantResolverFilter) and shows one unified message
 * regardless of the exact reason, so a probing visitor can't learn
 * which condition triggered it.
 */
class SubscriptionController extends Controller
{
    public function expired()
    {
        return view('subscription/expired', ['title' => 'Subscription Inactive']);
    }
}

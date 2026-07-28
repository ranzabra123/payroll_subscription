<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Blocks every route except the change-password page itself (and logout)
 * for a tenant user whose password was assigned by someone else
 * (initial company provisioning, or a superadmin credential reset) — see
 * `users.must_change_password`. Mirrors the allow-list pattern used by
 * TenantResolverFilter for subscription gating, including using
 * getPath() (not getUri()->getPath()) so it works correctly whether the
 * app is served from a subfolder or a vhost root.
 *
 * The user can defer this via ChangePasswordController::skip(), which
 * sets `must_change_password_skipped` in session only (the DB flag is
 * untouched, so they're prompted again on their next fresh login).
 */
class ForcePasswordChangeFilter implements FilterInterface
{
    private const ALLOWED_PATHS = ['change-password', 'change-password/skip', 'logout'];

    public function before(RequestInterface $request, $arguments = null): mixed
    {
        if (! session()->get('must_change_password') || session()->get('must_change_password_skipped')) {
            return null;
        }

        $path = trim($request->getPath(), '/');

        if (in_array($path, self::ALLOWED_PATHS, true)) {
            return null;
        }

        return redirect()->to(site_url('change-password'));
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null): mixed
    {
        return null;
    }
}

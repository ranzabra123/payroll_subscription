<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * SuperadminAuthFilter – enforces superadmin login for protected
 * /superadmin routes. Entirely separate from the tenant AuthFilter:
 * checks a non-overlapping session key so a tenant session and a
 * superadmin session can never be mistaken for each other.
 */
class SuperadminAuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null): mixed
    {
        if (! session()->get('superadmin_logged_in')) {
            return redirect()->to(site_url('superadmin/login'))->with('error', 'Please log in to continue.');
        }
        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null): mixed
    {
        return null;
    }
}

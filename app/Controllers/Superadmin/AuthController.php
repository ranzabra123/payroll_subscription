<?php

namespace App\Controllers\Superadmin;

use App\Models\Landlord\SuperadminModel;
use CodeIgniter\Controller;

/**
 * Superadmin\AuthController – login/logout for the superadmin console.
 * Deliberately mirrors App\Controllers\Auth but is a fully separate
 * stack: separate model, separate session keys, separate DB group
 * (landlord) — a superadmin session and a tenant session can never
 * be confused for one another.
 */
class AuthController extends Controller
{
    public function login()
    {
        if (session()->get('superadmin_logged_in')) {
            return redirect()->to(site_url('superadmin'));
        }
        return view('superadmin/auth/login', ['title' => 'Superadmin Login']);
    }

    public function attemptLogin()
    {
        $rules = [
            'username' => 'required|min_length[3]',
            'password' => 'required|min_length[4]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->with('errors', $this->validator->getErrors())->withInput();
        }

        $username = $this->request->getPost('username', FILTER_SANITIZE_SPECIAL_CHARS);
        $password = $this->request->getPost('password');

        $model = new SuperadminModel();
        $admin = $model->findActiveByUsername($username);

        if (! $admin || ! password_verify($password, $admin['password'])) {
            return redirect()->back()->with('error', 'Invalid username or password.')->withInput();
        }

        // Clear any pre-existing (e.g. tenant) session data first, then
        // regenerate the id, so a browser can never hold mixed tenant +
        // superadmin session state. (session()->destroy() alone would not
        // clear the in-memory $_SESSION array within this same request —
        // only session()->remove() does that.)
        session()->remove(array_keys($_SESSION));
        session()->regenerate(true);

        session()->set([
            'superadmin_id'       => $admin['id'],
            'superadmin_username' => $admin['username'],
            'superadmin_full_name' => $admin['full_name'],
            'superadmin_logged_in' => true,
        ]);

        $model->updateLastLogin($admin['id']);

        return redirect()->to(site_url('superadmin'));
    }

    public function logout()
    {
        session()->destroy();
        return redirect()->to(site_url('superadmin/login'))->with('success', 'You have been logged out.');
    }
}

<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\UserModel;
use App\Models\AuditLogModel;
use App\Models\Landlord\CompanyModel;
use App\Libraries\CredentialCipher;
use App\Libraries\TenantConnectionResolver;

/**
 * Auth – handles tenant login, logout.
 */
class Auth extends Controller
{
    /**
     * Generic error message used for every login failure reason (unknown
     * company code, unknown username, wrong password) so the login form
     * never reveals which part was wrong — prevents company-code enumeration.
     */
    private const INVALID_LOGIN_MESSAGE = 'Invalid company code, username, or password.';

    public function login()
    {
        if (session()->get('logged_in')) {
            return redirect()->to(site_url('dashboard'));
        }
        return view('auth/login', ['title' => 'Login']);
    }

    public function attemptLogin()
    {
        $rules = [
            'company_code' => 'required|alpha_dash|min_length[2]|max_length[50]',
            'username'     => 'required|min_length[3]',
            'password'     => 'required|min_length[4]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->with('errors', $this->validator->getErrors())->withInput();
        }

        $companyCode = $this->request->getPost('company_code', FILTER_SANITIZE_SPECIAL_CHARS);
        $username    = $this->request->getPost('username', FILTER_SANITIZE_SPECIAL_CHARS);
        $password    = $this->request->getPost('password');

        $company = (new CompanyModel())->findBySlug($companyCode);

        if (! $company) {
            return redirect()->back()->with('error', self::INVALID_LOGIN_MESSAGE)->withInput();
        }

        $decrypted               = $company;
        $decrypted['db_password'] = CredentialCipher::decrypt($company['db_password']);
        TenantConnectionResolver::applyToDefaultGroup($decrypted);

        $userModel = new UserModel();
        $user = $userModel->findActiveByUsername($username);

        if (! $user || ! password_verify($password, $user['password'])) {
            return redirect()->back()->with('error', self::INVALID_LOGIN_MESSAGE)->withInput();
        }

        // Clear any pre-existing (e.g. superadmin) session data first, then
        // regenerate the id, so a browser can never hold mixed tenant +
        // superadmin session state, and a session fixed/observed before
        // authentication can't be reused afterward.
        session()->remove(array_keys($_SESSION));
        session()->regenerate(true);

        session()->set([
            'user_id'               => $user['id'],
            'username'              => $user['username'],
            'full_name'             => $user['full_name'],
            'role'                  => $user['role'],
            'branch_id'             => $user['branch_id'] ?? null,
            'company_id'            => $company['id'],
            'company_slug'          => $company['slug'],
            'must_change_password'  => (bool) ($user['must_change_password'] ?? false),
            'logged_in'             => true,
        ]);

        $userModel->updateLastLogin($user['id']);

        // Audit
        $ip = $this->request->getIPAddress();
        (new AuditLogModel())->logAction('Auth', 'login', $user['id'], null, null,
            "User '{$user['username']}' logged in from {$ip}");

        return redirect()->to(site_url('dashboard'));
    }

    public function logout()
    {
        $logUsername = session()->get('username') ?? 'unknown';
        (new AuditLogModel())->logAction('Auth', 'logout', session()->get('user_id'), null, null,
            "User '{$logUsername}' logged out");
        session()->destroy();
        return redirect()->to(site_url('login'))->with('success', 'You have been logged out.');
    }
}

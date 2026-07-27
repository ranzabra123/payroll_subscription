<?php

namespace App\Controllers\Superadmin;

use App\Libraries\CredentialCipher;
use App\Libraries\ProvisioningException;
use App\Libraries\ProvisioningService;
use App\Libraries\SubscriptionPlans;
use App\Libraries\TenantConnectionResolver;
use App\Models\Landlord\CompanyModel;
use App\Models\UserModel;
use CodeIgniter\Controller;
use CodeIgniter\Database\BaseConnection;
use Config\Database;

class CompaniesController extends Controller
{
    protected CompanyModel $model;

    public function __construct()
    {
        $this->model = new CompanyModel();
    }

    public function index()
    {
        $companies = $this->model->orderBy('created_at', 'DESC')->findAll();
        return view('superadmin/companies/index', ['title' => 'Companies', 'companies' => $companies]);
    }

    public function create()
    {
        return view('superadmin/companies/create', ['title' => 'Add Company', 'plans' => SubscriptionPlans::PLANS]);
    }

    public function store()
    {
        $rules = [
            'name'            => 'required|min_length[2]|max_length[150]',
            'slug'            => 'required|regex_match[/^[a-z0-9_]{2,40}$/]',
            'plan'            => 'required|in_list[' . implode(',', SubscriptionPlans::keys()) . ']',
            'admin_username'  => 'required|min_length[3]|max_length[100]',
            'admin_password'  => 'required|min_length[6]',
            'admin_full_name' => 'required|min_length[3]|max_length[150]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->with('errors', $this->validator->getErrors())->withInput();
        }

        try {
            $company = (new ProvisioningService())->provision(
                $this->request->getPost('name', FILTER_SANITIZE_SPECIAL_CHARS),
                $this->request->getPost('slug', FILTER_SANITIZE_SPECIAL_CHARS),
                $this->request->getPost('admin_username', FILTER_SANITIZE_SPECIAL_CHARS),
                $this->request->getPost('admin_password'),
                $this->request->getPost('admin_full_name', FILTER_SANITIZE_SPECIAL_CHARS),
                $this->request->getPost('plan'),
            );
        } catch (ProvisioningException $e) {
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }

        return redirect()->to(site_url('superadmin/companies/view/' . $company['id']))
            ->with('success', "Company '{$company['name']}' provisioned successfully.");
    }

    public function view(int $id)
    {
        $company = $this->model->find($id);
        if (! $company) {
            return redirect()->to(site_url('superadmin/companies'))->with('error', 'Company not found.');
        }

        $tenantUsers = [];
        try {
            $conn = $this->tenantConnection($company);
            $tenantUsers = $conn->table('users')
                ->select('id, username, full_name, role, status')
                ->orderBy('role', 'ASC')
                ->orderBy('username', 'ASC')
                ->get()->getResultArray();
        } catch (\Throwable) {
            // Tenant DB unreachable — still show the company page, just
            // without the credentials panel populated.
        }

        return view('superadmin/companies/view', [
            'title'       => $company['name'],
            'company'     => $company,
            'tenantUsers' => $tenantUsers,
            'plans'       => SubscriptionPlans::PLANS,
        ]);
    }

    /** Upgrade or downgrade a company's subscription plan. */
    public function updatePlan(int $id)
    {
        $company = $this->model->find($id);
        if (! $company) {
            return redirect()->to(site_url('superadmin/companies'))->with('error', 'Company not found.');
        }

        $rules = ['plan' => 'required|in_list[' . implode(',', SubscriptionPlans::keys()) . ']'];
        if (! $this->validate($rules)) {
            return redirect()->to(site_url('superadmin/companies/view/' . $id))->with('error', 'Invalid plan.');
        }

        $plan = $this->request->getPost('plan');
        $this->model->update($id, ['subscription_plan' => $plan]);

        return redirect()->to(site_url('superadmin/companies/view/' . $id))
            ->with('success', "Plan updated to '" . SubscriptionPlans::label($plan) . "'.");
    }

    /**
     * Superadmin-initiated username/password reset for a tenant user —
     * e.g. when a company's admin is locked out. Operates directly on the
     * tenant's own `users` table via a live connection to that tenant's
     * database (same mechanism ProvisioningService uses), never touching
     * the `default` group.
     */
    public function resetCredentials(int $id)
    {
        $company = $this->model->find($id);
        if (! $company) {
            return redirect()->to(site_url('superadmin/companies'))->with('error', 'Company not found.');
        }

        $rules = [
            'user_id'  => 'required|is_natural_no_zero',
            'username' => 'required|min_length[3]|max_length[100]',
            'password' => 'permit_empty|min_length[6]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->to(site_url('superadmin/companies/view/' . $id))
                ->with('errors', $this->validator->getErrors());
        }

        try {
            $conn = $this->tenantConnection($company);
        } catch (\Throwable $e) {
            return redirect()->to(site_url('superadmin/companies/view/' . $id))
                ->with('error', 'Could not connect to the tenant database: ' . $e->getMessage());
        }

        $userId    = (int) $this->request->getPost('user_id');
        $username  = $this->request->getPost('username', FILTER_SANITIZE_SPECIAL_CHARS);
        $userModel = new UserModel($conn);

        $user = $userModel->find($userId);
        if (! $user) {
            return redirect()->to(site_url('superadmin/companies/view/' . $id))
                ->with('error', 'That user no longer exists in this company.');
        }

        // UserModel's own `is_unique[users.username,...]` rule resolves its
        // DB connection by group *name* (via $this->DBGroup, which is null
        // here since UserModel is deliberately tenant-agnostic), not by the
        // connection object injected into the constructor — so it would
        // silently check the wrong database. Check uniqueness manually
        // against the actual tenant connection instead, and skip the
        // model's built-in validation for this update.
        $duplicate = $conn->table('users')
            ->where('username', $username)
            ->where('id !=', $userId)
            ->countAllResults();

        if ($duplicate > 0) {
            return redirect()->to(site_url('superadmin/companies/view/' . $id))
                ->with('error', "Username '{$username}' is already taken in this company.");
        }

        $data = ['username' => $username];

        $password = $this->request->getPost('password');
        if (! empty($password)) {
            $data['password'] = $password;
            // Superadmin is choosing this password on the user's behalf,
            // so treat it as temporary — force them to set their own on
            // next login.
            $data['must_change_password'] = 1;
        }

        if (! $userModel->skipValidation(true)->update($userId, $data)) {
            return redirect()->to(site_url('superadmin/companies/view/' . $id))
                ->with('errors', $userModel->errors());
        }

        return redirect()->to(site_url('superadmin/companies/view/' . $id))
            ->with('success', "Credentials updated for '{$data['username']}'.");
    }

    /**
     * Builds a live connection to a company's tenant database from its
     * (encrypted) stored credentials. Never mutates the `default` group —
     * this is an isolated connection object, same pattern as
     * ProvisioningService and TenantConnectionResolver::connectionArrayFor.
     */
    private function tenantConnection(array $company): BaseConnection
    {
        $company['db_password'] = CredentialCipher::decrypt($company['db_password']);

        return Database::connect(TenantConnectionResolver::connectionArrayFor($company), false);
    }

    public function updateStatus(int $id)
    {
        $company = $this->model->find($id);
        if (! $company) {
            return redirect()->to(site_url('superadmin/companies'))->with('error', 'Company not found.');
        }

        $rules = ['status' => 'required|in_list[trial,active,suspended,cancelled]'];
        if (! $this->validate($rules)) {
            return redirect()->back()->with('error', 'Invalid status.');
        }

        $this->model->update($id, ['status' => $this->request->getPost('status')]);

        return redirect()->to(site_url('superadmin/companies/view/' . $id))->with('success', 'Company status updated.');
    }

    public function updateExpiry(int $id)
    {
        $company = $this->model->find($id);
        if (! $company) {
            return redirect()->to(site_url('superadmin/companies'))->with('error', 'Company not found.');
        }

        $rules = ['subscription_expires_at' => 'required|valid_date'];
        if (! $this->validate($rules)) {
            return redirect()->back()->with('error', 'Invalid date.');
        }

        $this->model->update($id, [
            'subscription_expires_at' => date('Y-m-d H:i:s', strtotime($this->request->getPost('subscription_expires_at'))),
        ]);

        return redirect()->to(site_url('superadmin/companies/view/' . $id))->with('success', 'Subscription expiry updated.');
    }
}

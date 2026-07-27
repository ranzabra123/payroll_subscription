<?php

namespace App\Controllers\Superadmin;

use App\Models\Landlord\SuperadminModel;
use CodeIgniter\Controller;

/**
 * SuperadminsController – lets an existing superadmin manage other
 * superadmin accounts (add/edit/delete) from the console, instead of
 * only via the `php spark superadmin:create` CLI command.
 */
class SuperadminsController extends Controller
{
    protected SuperadminModel $model;

    public function __construct()
    {
        $this->model = new SuperadminModel();
    }

    public function index()
    {
        $admins = $this->model->orderBy('full_name', 'ASC')->findAll();
        return view('superadmin/admins/index', ['title' => 'Superadmins', 'admins' => $admins]);
    }

    public function create()
    {
        return view('superadmin/admins/create', ['title' => 'Add Superadmin']);
    }

    public function store()
    {
        $rules = [
            // Explicit 3-part "dbGroup.table.field" form — without a
            // dbGroup prefix, is_unique falls back to whatever `default`
            // currently is (see the resetCredentials() docblock in
            // CompaniesController for the same footgun hit there), which
            // for a superadmin request is never the `landlord` group.
            'username'  => 'required|min_length[3]|max_length[100]|is_unique[landlord.superadmins.username]',
            'password'  => 'required|min_length[6]',
            'full_name' => 'required|min_length[3]|max_length[150]',
            'status'    => 'required|in_list[active,inactive]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->with('errors', $this->validator->getErrors())->withInput();
        }

        $this->model->insert([
            'username'  => $this->request->getPost('username', FILTER_SANITIZE_SPECIAL_CHARS),
            'password'  => $this->request->getPost('password'),
            'full_name' => $this->request->getPost('full_name', FILTER_SANITIZE_SPECIAL_CHARS),
            'status'    => $this->request->getPost('status'),
        ]);

        return redirect()->to(site_url('superadmin/admins'))->with('success', 'Superadmin created successfully.');
    }

    public function edit(int $id)
    {
        $admin = $this->model->find($id);
        if (! $admin) {
            return redirect()->to(site_url('superadmin/admins'))->with('error', 'Superadmin not found.');
        }
        return view('superadmin/admins/edit', ['title' => 'Edit Superadmin', 'admin' => $admin]);
    }

    public function update(int $id)
    {
        $admin = $this->model->find($id);
        if (! $admin) {
            return redirect()->to(site_url('superadmin/admins'))->with('error', 'Superadmin not found.');
        }

        $rules = [
            'username'  => "required|min_length[3]|max_length[100]|is_unique[landlord.superadmins.username,id,{$id}]",
            'full_name' => 'required|min_length[3]|max_length[150]',
            'status'    => 'required|in_list[active,inactive]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->with('errors', $this->validator->getErrors())->withInput();
        }

        $newStatus = $this->request->getPost('status');

        // Never allow the last active superadmin to be deactivated — the
        // console would become permanently inaccessible to everyone.
        if ($admin['status'] === 'active' && $newStatus !== 'active') {
            $activeCount = $this->model->where('status', 'active')->countAllResults();
            if ($activeCount <= 1) {
                return redirect()->back()->with('error', 'Cannot deactivate the last active superadmin.')->withInput();
            }
        }

        $data = [
            'username'  => $this->request->getPost('username', FILTER_SANITIZE_SPECIAL_CHARS),
            'full_name' => $this->request->getPost('full_name', FILTER_SANITIZE_SPECIAL_CHARS),
            'status'    => $newStatus,
        ];

        $pw = $this->request->getPost('password');
        if (! empty($pw)) {
            if (strlen($pw) < 6) {
                return redirect()->back()->with('error', 'Password must be at least 6 characters.')->withInput();
            }
            $data['password'] = $pw;
        }

        $this->model->skipValidation(true)->update($id, $data);

        return redirect()->to(site_url('superadmin/admins'))->with('success', 'Superadmin updated successfully.');
    }

    public function delete(int $id)
    {
        if ($id === (int) session()->get('superadmin_id')) {
            return redirect()->to(site_url('superadmin/admins'))->with('error', 'You cannot delete your own account.');
        }

        $admin = $this->model->find($id);
        if (! $admin) {
            return redirect()->to(site_url('superadmin/admins'))->with('error', 'Superadmin not found.');
        }

        // Never allow the last active superadmin to be removed.
        if ($admin['status'] === 'active') {
            $activeCount = $this->model->where('status', 'active')->countAllResults();
            if ($activeCount <= 1) {
                return redirect()->to(site_url('superadmin/admins'))->with('error', 'Cannot delete the last active superadmin.');
            }
        }

        $this->model->delete($id);

        return redirect()->to(site_url('superadmin/admins'))->with('success', 'Superadmin deleted successfully.');
    }
}

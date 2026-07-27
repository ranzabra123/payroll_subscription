<?php

namespace App\Controllers;

use App\Models\AuditLogModel;
use App\Models\UserModel;
use CodeIgniter\Controller;

/**
 * Forces a user to set their own password before they can do anything
 * else in the system, whenever `users.must_change_password` is set —
 * i.e. their current password was assigned by someone else (initial
 * company provisioning, or a superadmin credential reset). Gated by
 * ForcePasswordChangeFilter, which blocks every other route while this
 * flag is set.
 */
class ChangePasswordController extends Controller
{
    public function index()
    {
        return view('auth/change_password', ['title' => 'Change Your Password']);
    }

    public function update()
    {
        $rules = [
            'current_password' => 'required',
            'new_password'     => 'required|min_length[6]',
            'confirm_password' => 'required|matches[new_password]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->with('errors', $this->validator->getErrors());
        }

        $userModel = new UserModel();
        $user      = $userModel->find(session()->get('user_id'));

        if (! $user || ! password_verify($this->request->getPost('current_password'), $user['password'])) {
            return redirect()->back()->with('error', 'Current password is incorrect.');
        }

        $newPassword = $this->request->getPost('new_password');

        if (password_verify($newPassword, $user['password'])) {
            return redirect()->back()->with('error', 'New password must be different from your current password.');
        }

        $userModel->update($user['id'], [
            'password'              => $newPassword,
            'must_change_password'  => 0,
        ]);

        session()->set('must_change_password', false);

        (new AuditLogModel())->logAction('Auth', 'change_password', $user['id'], null, null,
            "User '{$user['username']}' set their own password");

        return redirect()->to(site_url('dashboard'))->with('success', 'Password updated. Welcome!');
    }
}

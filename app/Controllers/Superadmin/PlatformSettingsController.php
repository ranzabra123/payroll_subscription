<?php

namespace App\Controllers\Superadmin;

use App\Models\Landlord\PlatformSettingModel;
use CodeIgniter\Controller;

/**
 * Platform-wide branding (shown before any tenant is known: the shared
 * login page, the superadmin console, and the browser tab favicon/title
 * on those pages) — distinct from each tenant's own per-company branding
 * in SettingsController.
 */
class PlatformSettingsController extends Controller
{
    protected PlatformSettingModel $settings;

    public function __construct()
    {
        $this->settings = new PlatformSettingModel();
    }

    public function index()
    {
        return view('superadmin/settings/index', [
            'title' => 'Platform Settings',
            'cfg'   => $this->settings->getAllKeyed(),
        ]);
    }

    /** Save the browser tab title / brand name. */
    public function saveGeneral()
    {
        $title = $this->request->getPost('site_title', FILTER_SANITIZE_SPECIAL_CHARS);

        if (empty($title)) {
            return redirect()->to(site_url('superadmin/settings'))
                ->with('error', 'Site title cannot be empty.');
        }

        $this->settings->setValue('site_title', $title);

        return redirect()->to(site_url('superadmin/settings'))->with('success', 'Platform settings saved.');
    }

    /** Upload / replace the platform logo (used as favicon). */
    public function uploadLogo()
    {
        $file = $this->request->getFile('logo');

        if (! $file || ! $file->isValid() || $file->hasMoved()) {
            return redirect()->to(site_url('superadmin/settings'))
                ->with('error', 'No valid file uploaded.');
        }

        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml', 'image/x-icon', 'image/vnd.microsoft.icon'];
        if (! in_array($file->getMimeType(), $allowed, true)) {
            return redirect()->to(site_url('superadmin/settings'))
                ->with('error', 'Only image files are allowed (JPG, PNG, GIF, WEBP, SVG, ICO).');
        }
        if ($file->getSizeByUnit('mb') > 2) {
            return redirect()->to(site_url('superadmin/settings'))
                ->with('error', 'Logo must be under 2 MB.');
        }

        $oldPath = $this->settings->getValue('logo_path');
        if ($oldPath) {
            $oldFile = FCPATH . $oldPath;
            if (! file_exists($oldFile) && file_exists(FCPATH . 'public/' . $oldPath)) {
                $oldFile = FCPATH . 'public/' . $oldPath;
            }
            if (file_exists($oldFile)) {
                @unlink($oldFile);
            }
        }

        // A dedicated top-level folder (not assets/uploads/<slug>/, which
        // is reserved for per-tenant logos) so a future tenant slug can
        // never collide with the platform logo's storage location.
        $uploadDir = FCPATH . 'assets/platform/';
        if (! is_dir(FCPATH . 'assets') && is_dir(FCPATH . 'public/assets')) {
            $uploadDir = FCPATH . 'public/assets/platform/';
        }
        if (! is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $newName = 'logo_' . time() . '.' . $file->getExtension();
        $file->move($uploadDir, $newName);

        $relativePath = 'assets/platform/' . $newName;
        $this->settings->setValue('logo_path', $relativePath);

        return redirect()->to(site_url('superadmin/settings'))->with('success', 'Logo updated successfully.');
    }

    public function removeLogo()
    {
        $oldPath = $this->settings->getValue('logo_path');
        if ($oldPath) {
            $oldFile = FCPATH . $oldPath;
            if (! file_exists($oldFile) && file_exists(FCPATH . 'public/' . $oldPath)) {
                $oldFile = FCPATH . 'public/' . $oldPath;
            }
            if (file_exists($oldFile)) {
                @unlink($oldFile);
            }
        }
        $this->settings->setValue('logo_path', null);

        return redirect()->to(site_url('superadmin/settings'))->with('success', 'Logo removed.');
    }
}

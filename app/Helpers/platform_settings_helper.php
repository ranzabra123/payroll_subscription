<?php

/**
 * Platform Settings Helper
 * Provides platform_setting()/platform_logo_url() to fetch branding shown
 * before any tenant is known (shared login page, superadmin console,
 * favicon) — backed by the landlord DB's `platform_settings` table.
 * Mirrors app/Helpers/settings_helper.php, which does the same thing
 * per-tenant.
 */

if (! function_exists('platform_setting')) {
    function platform_setting(string $key, string $default = ''): string
    {
        static $cache = null;

        if ($cache === null) {
            try {
                $model = new \App\Models\Landlord\PlatformSettingModel();
                $cache = $model->getAllKeyed();
            } catch (\Throwable $e) {
                $cache = [];
            }
        }

        return isset($cache[$key]) && $cache[$key] !== null
            ? $cache[$key]
            : $default;
    }
}

if (! function_exists('platform_logo_url')) {
    /** Returns the platform logo URL, or null if none is set. */
    function platform_logo_url(): ?string
    {
        $path = platform_setting('logo_path');
        if (! $path) {
            return null;
        }

        $candidates = [$path];
        if (str_starts_with($path, 'public/')) {
            $candidates[] = substr($path, 7);
        } else {
            $candidates[] = 'public/' . $path;
        }

        foreach ($candidates as $candidate) {
            if (file_exists(FCPATH . $candidate)) {
                if (str_starts_with($candidate, 'public/')) {
                    $candidate = substr($candidate, 7);
                }
                return base_url($candidate);
            }
        }

        return null;
    }
}

<?php

/**
 * Settings Helper
 * Provides a global setting() function to fetch CMS settings from the DB.
 * Values are cached in a static array for the request lifetime.
 */

if (! function_exists('setting')) {
    function setting(string $key, string $default = ''): string
    {
        static $cache = null;

        if ($cache === null) {
            try {
                $model = new \App\Models\SettingModel();
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

if (! function_exists('setting_logo_url')) {
    /** Returns the logo URL, or null if no logo is set. */
    function setting_logo_url(): ?string
    {
        $path = setting('logo_path');
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

if (! function_exists('contrast_text_color')) {
    /**
     * Given a background hex color, returns a readable text color
     * (dark or light) for it based on perceived brightness. Used for the
     * topbar, whose background is user-configurable via theme presets —
     * some presets (e.g. dark topbars) would otherwise collide with the
     * topbar's hardcoded dark text color in custom.css.
     */
    function contrast_text_color(string $hex, string $dark = '#1e293b', string $light = '#f8fafc'): string
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        if (strlen($hex) !== 6 || ! ctype_xdigit($hex)) {
            return $dark;
        }

        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        $brightness = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;

        return $brightness > 0.55 ? $dark : $light;
    }
}

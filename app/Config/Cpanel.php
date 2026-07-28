<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * cPanel UAPI credentials, used by CpanelApiService as a fallback way to
 * create tenant databases + grant privileges when the app's own MySQL
 * user has no CREATE DATABASE privilege (the norm on shared/cPanel
 * hosting). Optional — ProvisioningService only uses this if all four
 * values are set; otherwise it falls back to the manual pre-create flow.
 *
 * Override via .env:
 *   cpanel.host     = <server IP — not the domain, if it's behind Cloudflare>
 *   cpanel.port     = 2083
 *   cpanel.username = mrcyjkmp
 *   cpanel.apiToken = <generated via cPanel > Security > Manage API Tokens>
 */
class Cpanel extends BaseConfig
{
    public string $host = '';
    public int $port = 2083;
    public string $username = '';
    public string $apiToken = '';
}

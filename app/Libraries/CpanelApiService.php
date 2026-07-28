<?php

namespace App\Libraries;

use Config\Cpanel;

/**
 * Thin client for the handful of cPanel UAPI calls ProvisioningService
 * needs (create a database, grant a user full privileges on it) — used
 * as a fallback on hosts where the app's own MySQL user has no CREATE
 * DATABASE privilege (typical on shared/cPanel hosting), since cPanel's
 * API can do this on the account's behalf even when the MySQL user
 * itself can't.
 *
 * @see https://api.docs.cpanel.net/openapi/cpanel/operation/Mysql-create_database/
 * @see https://api.docs.cpanel.net/openapi/cpanel/operation/Mysql-set_privileges_on_database/
 */
class CpanelApiService
{
    private string $host;
    private int $port;
    private string $username;
    private string $token;

    public function __construct(?string $host = null, ?int $port = null, ?string $username = null, ?string $token = null)
    {
        $cfg = config(Cpanel::class);

        $this->host     = $host ?? $cfg->host;
        $this->port     = $port ?? $cfg->port;
        $this->username = $username ?? $cfg->username;
        $this->token     = $token ?? $cfg->apiToken;
    }

    public function isConfigured(): bool
    {
        return $this->host !== '' && $this->username !== '' && $this->token !== '';
    }

    /**
     * Creates a database via cPanel. Pass the bare suffix — no account
     * prefix — cPanel prepends it automatically (the same way its own
     * "MySQL Databases" page works), matching Config\Database::$tenantDbPrefix.
     */
    public function createDatabase(string $dbNameSuffix): void
    {
        $this->call('Mysql', 'create_database', ['name' => $dbNameSuffix]);
    }

    /**
     * Grants a DB user ALL PRIVILEGES on a database. Both arguments are
     * bare suffixes (no account prefix), same reasoning as above.
     */
    public function grantAllPrivileges(string $dbNameSuffix, string $dbUserSuffix): void
    {
        $this->call('Mysql', 'set_privileges_on_database', [
            'user'       => $dbUserSuffix,
            'database'   => $dbNameSuffix,
            'privileges' => 'ALL PRIVILEGES',
        ]);
    }

    /** Builds the UAPI request URL — split out from call() so it's independently testable. */
    protected function buildUrl(string $module, string $function, array $params): string
    {
        return sprintf('https://%s:%d/execute/%s/%s?%s', $this->host, $this->port, $module, $function, http_build_query($params));
    }

    /**
     * @return array<string, mixed> The decoded UAPI response.
     *
     * @throws CpanelApiException
     */
    protected function call(string $module, string $function, array $params): array
    {
        $ch = curl_init($this->buildUrl($module, $function, $params));
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ['Authorization: cpanel ' . $this->username . ':' . $this->token],
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);

        $raw       = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($raw === false || $raw === '') {
            throw new CpanelApiException("cPanel API request to {$module}::{$function} failed: {$curlError}");
        }

        return $this->parseResponse($raw, $httpCode, $module, $function);
    }

    /**
     * Parses+validates a raw UAPI JSON response body. Split out from
     * call() so this logic (the part that actually matters — correctly
     * interpreting cPanel's response format) is testable without a live
     * network call.
     *
     * @return array<string, mixed>
     *
     * @throws CpanelApiException
     */
    protected function parseResponse(string $raw, int $httpCode, string $module, string $function): array
    {
        $decoded = json_decode($raw, true);

        if (! is_array($decoded) || ! array_key_exists('status', $decoded)) {
            throw new CpanelApiException(
                "cPanel API returned an unexpected response for {$module}::{$function} (HTTP {$httpCode}): "
                . substr($raw, 0, 300),
            );
        }

        if ((int) $decoded['status'] !== 1) {
            $errors = implode('; ', $decoded['errors'] ?? ['Unknown cPanel API error']);
            throw new CpanelApiException("cPanel API error from {$module}::{$function}: {$errors}");
        }

        return $decoded;
    }
}

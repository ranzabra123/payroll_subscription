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
     * Creates a database via cPanel. Pass the FULL name including the
     * account prefix (e.g. "mrcyjkmp_the_boundary_cafe") — verified
     * empirically against a real cPanel install: unlike some cPanel
     * documentation examples, this UAPI does NOT auto-prepend the
     * account prefix and rejects names that don't already start with
     * it ("does not begin with the required prefix").
     */
    public function createDatabase(string $fullDbName): void
    {
        $this->call('Mysql', 'create_database', ['name' => $fullDbName]);
    }

    /**
     * Grants a DB user ALL PRIVILEGES on a database. Both arguments are
     * FULL names including the account prefix, same reasoning as above
     * — a bare username suffix fails here too.
     */
    public function grantAllPrivileges(string $fullDbName, string $fullDbUser): void
    {
        $this->call('Mysql', 'set_privileges_on_database', [
            'user'       => $fullDbUser,
            'database'   => $fullDbName,
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

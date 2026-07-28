<?php

namespace Tests\Unit;

use App\Libraries\CpanelApiException;
use App\Libraries\CpanelApiService;
use CodeIgniter\Test\CIUnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests the response-parsing/URL-building logic in isolation from the
 * actual network call, since there's no real cPanel server to test
 * against locally. A TestableCpanelApiService subclass exposes the
 * normally-protected methods so they can be exercised directly.
 *
 * @internal
 */
final class CpanelApiServiceTest extends CIUnitTestCase
{
    public function testIsConfiguredRequiresAllFourValues(): void
    {
        $this->assertFalse((new CpanelApiService('', 2083, 'user', 'token'))->isConfigured());
        $this->assertFalse((new CpanelApiService('host', 2083, '', 'token'))->isConfigured());
        $this->assertFalse((new CpanelApiService('host', 2083, 'user', ''))->isConfigured());
        $this->assertTrue((new CpanelApiService('host', 2083, 'user', 'token'))->isConfigured());
    }

    public function testBuildUrlUsesBareParamsWithoutLeakingTheTokenIntoTheUrl(): void
    {
        $service = new TestableCpanelApiService('1.2.3.4', 2083, 'mrcyjkmp', 'secret-token');

        $url = $service->publicBuildUrl('Mysql', 'create_database', ['name' => 'the_boundary_cafe']);

        $this->assertSame('https://1.2.3.4:2083/execute/Mysql/create_database?name=the_boundary_cafe', $url);
        $this->assertStringNotContainsString('secret-token', $url);
    }

    public function testParseResponseReturnsDecodedArrayOnSuccess(): void
    {
        $service = new TestableCpanelApiService('h', 1, 'u', 't');

        $result = $service->publicParseResponse(
            json_encode(['status' => 1, 'data' => ['name' => 'mrcyjkmp_the_boundary_cafe']]),
            200,
            'Mysql',
            'create_database',
        );

        $this->assertSame(1, $result['status']);
    }

    #[DataProvider('failureResponseProvider')]
    public function testParseResponseThrowsOnFailureOrMalformedBody(string $raw, int $httpCode): void
    {
        $service = new TestableCpanelApiService('h', 1, 'u', 't');

        $this->expectException(CpanelApiException::class);

        $service->publicParseResponse($raw, $httpCode, 'Mysql', 'create_database');
    }

    public static function failureResponseProvider(): array
    {
        return [
            'status 0 with errors' => [json_encode(['status' => 0, 'errors' => ['Database already exists.']]), 200],
            'not json'             => ['<html>502 Bad Gateway</html>', 502],
            'json but no status key' => [json_encode(['foo' => 'bar']), 200],
        ];
    }
}

/**
 * Exposes CpanelApiService's protected helpers for direct testing.
 */
class TestableCpanelApiService extends CpanelApiService
{
    public function publicBuildUrl(string $module, string $function, array $params): string
    {
        return $this->buildUrl($module, $function, $params);
    }

    public function publicParseResponse(string $raw, int $httpCode, string $module, string $function): array
    {
        return $this->parseResponse($raw, $httpCode, $module, $function);
    }
}

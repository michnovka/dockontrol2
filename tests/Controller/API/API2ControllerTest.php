<?php

declare(strict_types=1);

namespace App\Tests\Controller\API;

use App\Entity\Log\ApiCallFailedLog\API2CallFailedLog;
use App\Entity\Log\ApiCallLog\API2CallLog;
use App\Tests\Controller\BaseControllerTestCase;
use Carbon\CarbonImmutable;
use Override;
use Symfony\Component\Uid\Uuid;

class API2ControllerTest extends BaseControllerTestCase
{
    private const string API_ENDPOINT_PREFIX = '/api/2';

    private string $privateKey;
    private string $publicKey;

    public function testValidRequest(): void
    {
        $timestamp = CarbonImmutable::now();
        /** @var string $body*/
        $body = json_encode(['foo' => 'bar']);
        $headers = $this->generateHeaders($timestamp->getTimestamp(), 'POST', self::API_ENDPOINT_PREFIX . '/info', $body);
        $this->makeRequest('POST', self::API_ENDPOINT_PREFIX . '/info', $headers, $body);
        $this->assertResponseIsSuccessful();

        $response = $this->getResponseContent();

        if (is_string($response)) {
            $responseData = json_decode($response, true);
            $this->assertArrayHasKey('time', $responseData);
            $this->assertArrayHasKey('dockontrol_version', $responseData);
            $logCount = $this->getLogCount(API2CallLog::class);
            $this->assertSame(1, $logCount, 'Expected exactly 1 success log entry.');
        }
    }

    public function testInvalidSignature(): void
    {
        $timestamp = CarbonImmutable::now();
        /** @var string $body*/
        $body = json_encode(['foo' => 'bar']);
        $headers = $this->generateHeaders($timestamp->getTimestamp(), 'POST', self::API_ENDPOINT_PREFIX . '/info', $body);
        $headers['HTTP_X-API-Signature'] = 'invalid_signature';
        $this->makeRequest('POST', self::API_ENDPOINT_PREFIX . '/info', $headers, $body);
        $this->assertResponseStatusCodeSame(401);
        $logCount = $this->getLogCount(API2CallFailedLog::class);
        $this->assertSame(1, $logCount, 'Expected exactly 1 failed log entry.');
        /** @var API2CallFailedLog $latestLogEntry*/
        $latestLogEntry = $this->getLatestLogEntry(API2CallFailedLog::class);
        $this->assertSame($this->publicKey, $latestLogEntry->getApiKey(), 'Expected same public key.');
    }

    public function testUnknownPublicKey(): void
    {
        $timestamp = CarbonImmutable::now();
        /** @var string $body*/
        $body = json_encode(['foo' => 'bar']);
        $headers = $this->generateHeaders($timestamp->getTimestamp(), 'POST', self::API_ENDPOINT_PREFIX . '/info', $body);
        $headers['HTTP_X-API-Key'] = Uuid::v7()->toString();
        $this->makeRequest('POST', self::API_ENDPOINT_PREFIX . '/info', $headers, $body);
        $this->assertResponseStatusCodeSame(401);
        $logCount = $this->getLogCount(API2CallFailedLog::class);
        $this->assertSame(1, $logCount, 'Expected exactly 1 failed log entry.');
    }

    public function testExpiredTimestamp(): void
    {
        $timestamp = CarbonImmutable::now()->subMinutes(5);
        /** @var string $body*/
        $body = json_encode(['foo' => 'bar']);
        $headers = $this->generateHeaders($timestamp->getTimestamp(), 'POST', self::API_ENDPOINT_PREFIX . '/info', $body);
        $this->makeRequest('POST', self::API_ENDPOINT_PREFIX . '/info', $headers, $body);
        $this->assertResponseStatusCodeSame(401);
        $logCount = $this->getLogCount(API2CallFailedLog::class);
        $this->assertSame(1, $logCount, 'Expected exactly 1 failed log entry.');
    }

    public function testInvalidApiUrl(): void
    {
        $timestamp = CarbonImmutable::now();
        /** @var string $body*/
        $body = json_encode(['foo' => 'bar']);
        $headers = $this->generateHeaders($timestamp->getTimestamp(), 'POST', self::API_ENDPOINT_PREFIX . '/invalid', $body);
        $this->makeRequest('POST', self::API_ENDPOINT_PREFIX . '/invalid', $headers, $body);

        $this->assertResponseStatusCodeSame(404);
        $logCount = $this->getLogCount(API2CallFailedLog::class);
        $this->assertSame(1, $logCount, 'Expected exactly 1 failed log entry.');
    }

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->truncateTable(API2CallLog::class);
        $this->truncateTable(API2CallFailedLog::class);

        /** @var string $publicAPIKey */
        $publicAPIKey = self::getContainer()->getParameter('public_key_for_test');
        $this->publicKey = $publicAPIKey;

        /** @var string $privateAPIKey */
        $privateAPIKey = self::getContainer()->getParameter('private_key_for_test');
        $this->privateKey = $privateAPIKey;
    }

    /**
     * @return array<string, string>
     */
    private function generateHeaders(int $timestamp, string $method, string $endpoint, string $body): array
    {
        $data = $timestamp . $method . $endpoint . $body;
        $signature = hash_hmac('sha256', $data, $this->privateKey);

        return [
            'HTTP_X-API-Key' => $this->publicKey,
            'HTTP_X-API-Timestamp' => (string) $timestamp,
            'HTTP_X-API-Signature' => $signature,
            'CONTENT_TYPE' => 'application/json',
        ];
    }

    #[Override]
    protected function tearDown(): void
    {
        parent::tearDown();
    }
}

<?php

declare(strict_types=1);

namespace App\Tests\Controller\API;

use App\Entity\Log\ApiCallFailedLog\LegacyAPICallFailedLog;
use App\Entity\Log\ApiCallLog\LegacyAPICallLog;
use App\Tests\Controller\BaseControllerTestCase;
use Override;

class LegacyAPIControllerTest extends BaseControllerTestCase
{
    private const string LEGACY_API_ENDPOINT = '/api.php';
    private const string API1_ENDPOINT = '/api/1';

    private string $email;
    private string $password;

    #[Override]
    public function setUp(): void
    {
        parent::setUp();

        /**  @var string $email */
        $email = self::getContainer()->getParameter('email_for_legacy_api_test');
        /** @var string $password */
        $password = self::getContainer()->getParameter('password_for_legacy_api_test');
        $this->email = $email;
        $this->password = $password;

        $this->truncateTable(LegacyAPICallLog::class);
        $this->truncateTable(LegacyAPICallFailedLog::class);
    }

    public function testValidLegacyApiRequest(): void
    {
        $this->makeRequest('POST', self::LEGACY_API_ENDPOINT, parameters: [
            'username' => $this->email,
            'password' => $this->password,
            'action' => 'app_login',
        ]);

        $this->assertResponseStatusCodeSame(200);
        $logCount = $this->getLogCount(LegacyAPICallLog::class);
        $this->assertSame(1, $logCount, 'Expected exactly 1 success log entry.');
    }

    public function testValidApi1Request(): void
    {
        $this->makeRequest('POST', self::API1_ENDPOINT, parameters: [
            'username' => $this->email,
            'password' => $this->password,
            'action' => 'app_login',
        ]);

        $this->assertResponseStatusCodeSame(200);
        $logCount = $this->getLogCount(LegacyAPICallLog::class);
        $this->assertSame(1, $logCount, 'Expected exactly 1 success log entry.');
    }

    public function testInvalidPassword(): void
    {
        $this->makeRequest('POST', self::API1_ENDPOINT, parameters: [
            'username' => $this->email,
            'password' => 'invalid',
            'action' => 'app_login',
        ]);
        $this->assertResponseStatusCodeSame(401);
        $logCount = $this->getLogCount(LegacyAPICallFailedLog::class);
        $this->assertSame(1, $logCount, 'Expected exactly 1 failed log entry.');
        /** @var LegacyAPICallFailedLog $latestLogEntry*/
        $latestLogEntry = $this->getLatestLogEntry(LegacyAPICallFailedLog::class);
        $this->assertSame($this->email, $latestLogEntry->getEmail(), 'Expected same email.');
    }

    public function testInvalidEmail(): void
    {
        $this->makeRequest('POST', self::API1_ENDPOINT, parameters: [
            'username' => 'unknown',
            'password' => $this->password,
            'action' => 'app_login',
        ]);
        $this->assertResponseStatusCodeSame(401);
        $logCount = $this->getLogCount(LegacyAPICallFailedLog::class);
        $this->assertSame(1, $logCount, 'Expected exactly 1 failed log entry.');
    }

    public function testInvalidApiUrl(): void
    {
        $this->makeRequest('POST', self::API1_ENDPOINT . '/invalid', parameters: [
            'username' => $this->email,
            'password' => $this->password,
            'action' => 'app_login',
        ]);
        $this->assertResponseStatusCodeSame(404);
        $logCount = $this->getLogCount(LegacyAPICallFailedLog::class);
        $this->assertSame(1, $logCount, 'Expected exactly 1 failed log entry.');
    }
}

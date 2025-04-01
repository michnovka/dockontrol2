<?php

declare(strict_types=1);

namespace App\Security\Credentials;

use App\Security\User\ApiKeyPairAuthenticatedUserInterface;
use Override;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\CredentialsInterface;

class APIKeyPairCredentials implements CredentialsInterface
{
    private bool $resolved = false;

    public function __construct(
        private readonly string $signature,
        private readonly int $timestamp,
        private readonly string $method,
        private readonly string $endpoint,
        private readonly string $body,
        private readonly int $timeout = 60,
    ) {
    }

    #[Override]
    public function isResolved(): bool
    {
        return $this->resolved;
    }

    public function validate(UserInterface $user): void
    {
        if (!$user instanceof ApiKeyPairAuthenticatedUserInterface) {
            throw new AuthenticationException('User does not support API key authentication.');
        }

        if ($this->isRequestExpired($this->timestamp)) {
            throw new AuthenticationException('Request has expired.');
        }

        $privateKey = $user->getPrivateKey();

        $data = $this->timestamp . $this->method . $this->endpoint . $this->body;

        $computedSignature = hash_hmac('sha256', $data, $privateKey);

        if (!hash_equals($computedSignature, $this->signature)) {
            throw new AuthenticationException('Invalid API key or signature.');
        }

        $this->resolved = true;
    }

    private function isRequestExpired(int $timestamp): bool
    {
        $currentTime = time();
        return abs($currentTime - $timestamp) >= $this->timeout;
    }
}

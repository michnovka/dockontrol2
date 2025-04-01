<?php

declare(strict_types=1);

namespace App\Security\Credentials;

use Override;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\CredentialsInterface;

class CameraSessionCredentials implements CredentialsInterface
{
    private bool $resolved = false;

    public function __construct(
        private readonly string $cameraId,
    ) {
    }

    #[Override]
    public function isResolved(): bool
    {
        return $this->resolved;
    }

    public function getCameraId(): string
    {
        return $this->cameraId;
    }

    public function markAsResolved(): void
    {
        $this->resolved = true;
    }
}

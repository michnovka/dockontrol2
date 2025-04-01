<?php

declare(strict_types=1);

namespace App\Tests\TestUtils\Stub;

use App\Entity\HasWireguardKeyPairInterface;
use Override;

class StubWireguardKeyPairEntity implements HasWireguardKeyPairInterface
{
    private string $privateKey = '';
    private string $publicKey = '';

    public function setWireguardPrivateKey(string $privateKey): void
    {
        $this->privateKey = $privateKey;
    }

    public function setWireguardPublicKey(string $publicKey): void
    {
        $this->publicKey = $publicKey;
    }

    #[Override]
    public function getWireguardPrivateKey(): string
    {
        return $this->privateKey;
    }

    #[Override]
    public function getWireguardPublicKey(): string
    {
        return $this->publicKey;
    }
}

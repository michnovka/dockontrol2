<?php

declare(strict_types=1);

namespace App\Entity;

interface HasWireguardKeyPairInterface
{
    public function getWireguardPublicKey(): string;

    public function getWireguardPrivateKey(): string;
}

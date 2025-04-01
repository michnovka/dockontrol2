<?php

declare(strict_types=1);

namespace App\Helper;

use SodiumException;

class WireguardHelper
{
    /**
     * Generates a WireGuard private key.
     *
     * @return string The private key encoded in Base64.
     * @throws SodiumException
     */
    public function generatePrivateKey(): string
    {
        // Generate a 32-byte random private key
        $privateKey = sodium_crypto_box_secretkey(sodium_crypto_box_keypair());

        // Encode the private key in Base64 for WireGuard
        return sodium_bin2base64($privateKey, SODIUM_BASE64_VARIANT_ORIGINAL);
    }

    /**
     * Generates the corresponding WireGuard public key from a given private key.
     *
     * @param string $privateKey The private key encoded in Base64.
     * @return string The public key encoded in Base64.
     * @throws SodiumException
     */
    public function generatePublicKey(string $privateKey): string
    {
        // Decode the private key from Base64
        $privateKeyDecoded = sodium_base642bin($privateKey, SODIUM_BASE64_VARIANT_ORIGINAL);

        // Derive the public key from the private key
        $publicKey = sodium_crypto_scalarmult_base($privateKeyDecoded);

        // Encode the public key in Base64 for WireGuard
        return sodium_bin2base64($publicKey, SODIUM_BASE64_VARIANT_ORIGINAL);
    }

    /**
     * Generates a complete WireGuard keypair.
     *
     * @return array{privateKey: string, publicKey: string}
     * @throws SodiumException
     */
    public function generateKeypair(): array
    {
        $privateKey = $this->generatePrivateKey();
        $publicKey = $this->generatePublicKey($privateKey);

        return [
            'privateKey' => $privateKey,
            'publicKey' => $publicKey,
        ];
    }
}

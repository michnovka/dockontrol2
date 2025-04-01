<?php

declare(strict_types=1);

namespace App\Tests\Helper;

use App\Helper\WireguardHelper;
use Override;
use PHPUnit\Framework\TestCase;
use SodiumException;

class WireguardHelperTest extends TestCase
{
    private WireguardHelper $wireguardHelper;

    #[Override]
    public function setUp(): void
    {
        parent::setUp();
        $this->wireguardHelper = new WireguardHelper();
    }

    public function testGeneratePublicKey(): void
    {
        try {
            $privateKey = 'qX8koGFX1jxlWlLDHDVR/2cU64xbASwEIfJVeOmtU90=';
            $publicKey = $this->wireguardHelper->generatePublicKey($privateKey);
            $this->assertEquals('BS7wCDN+cs2aAY4iPJzZAJ84VtIBGNoRCvNeQD7pLwQ=', $publicKey, "Public key incorrectly derived for private key.");
        } catch (SodiumException $e) {
            $this->fail('Exception: ' . $e->getMessage());
        }

        $this->assertTrue(base64_decode($publicKey, true) !== false);
    }


    public function testGenerateKeypair(): void
    {
        try {
            $keypair = $this->wireguardHelper->generateKeypair();
            $this->assertTrue(true, "Keypair generation failed");

            $privateKey = base64_decode($keypair['privateKey']);
            $publicKey = base64_decode($keypair['publicKey']);

            // Assert the private key is 32 bytes (256 bits) long
            $this->assertEquals(SODIUM_CRYPTO_BOX_SECRETKEYBYTES, strlen($privateKey), "Invalid private key length.");

            // Generate the corresponding public key from the private key
            $derivedPublicKey = sodium_crypto_scalarmult_base($privateKey);

            // Assert that the derived public key matches the provided public key
            $this->assertTrue(hash_equals($derivedPublicKey, $publicKey), "The public key does not correspond to the private key.");

            // Optionally, securely wipe the private key from memory
            sodium_memzero($privateKey);
        } catch (SodiumException $e) {
            $this->fail('Exception: ' . $e->getMessage());
        }
    }

    public function testGeneratePrivateKey(): void
    {
        try {
            $key = $this->wireguardHelper->generatePrivateKey();
            $this->assertTrue(true);
        } catch (SodiumException $e) {
            $this->fail('Exception: ' . $e->getMessage());
        }

        // In the case of success there is no exception,
        // so just make sure the method returns a base64 encoded string
        $this->assertTrue(base64_decode($key, true) !== false);
    }
}

<?php

declare(strict_types=1);

namespace App\Tests\Validator;

use App\Helper\WireguardHelper;
use App\Tests\TestUtils\Stub\StubWireguardKeyPairEntity;
use App\Validator\WireguardKeyPair;
use App\Validator\WireguardKeyPairValidator;
use Override;
use PHPUnit\Framework\MockObject\MockObject;
use SodiumException;
use stdClass;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @extends ConstraintValidatorTestCase<WireguardKeyPairValidator>
 */
class WireguardKeyPairValidatorTest extends ConstraintValidatorTestCase
{
    private WireguardHelper&MockObject $wireguardHelper;

    public function testNullIsValid(): void
    {
        $this->validator->validate(null, new WireguardKeyPair());

        $this->assertNoViolation();
    }

    public function testInvalidValueType(): void
    {
        $this->expectException(UnexpectedValueException::class);

        $this->validator->validate(new stdClass(), new WireguardKeyPair());
    }

    public function testInvalidConstraintType(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $entity = new StubWireguardKeyPairEntity();
        $this->validator->validate($entity, new NotBlank());
    }

    public function testValidKeyPair(): void
    {
        $privateKey = 'qX8koGFX1jxlWlLDHDVR/2cU64xbASwEIfJVeOmtU90=';
        $publicKey = 'BS7wCDN+cs2aAY4iPJzZAJ84VtIBGNoRCvNeQD7pLwQ=';

        $entity = new StubWireguardKeyPairEntity();
        $entity->setWireguardPrivateKey($privateKey);
        $entity->setWireguardPublicKey($publicKey);

        $this->wireguardHelper->method('generatePublicKey')
            ->with($privateKey)
            ->willReturn($publicKey);

        $this->validator->validate($entity, new WireguardKeyPair());

        $this->assertNoViolation();
    }

    public function testInvalidKeyPair(): void
    {
        $privateKey = 'qX8koGFX1jxlWlLDHDVR/2cU64xbASwEIfJVeOmtU90=';
        $publicKey = 'BS7wCDN+cs2aAY4iPJzZAJ84VtIBGNoRCvNeQD7pLwQ=';
        $incorrectPublicKey = 'IncorrectPublicKeyAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=';

        $entity = new StubWireguardKeyPairEntity();
        $entity->setWireguardPrivateKey($privateKey);
        $entity->setWireguardPublicKey($incorrectPublicKey);

        $this->wireguardHelper->method('generatePublicKey')
            ->with($privateKey)
            ->willReturn($publicKey);

        $this->validator->validate($entity, new WireguardKeyPair());

        $this->buildViolation('The private key does not match the public key.')
            ->assertRaised();
    }

    public function testFailedPublicKeyGeneration(): void
    {
        $privateKey = 'qX8koGFX1jxlWlLDHDVR/2cU64xbASwEIfJVeOmtU90=';
        $publicKey = 'BS7wCDN+cs2aAY4iPJzZAJ84VtIBGNoRCvNeQD7pLwQ=';

        $entity = new StubWireguardKeyPairEntity();
        $entity->setWireguardPrivateKey($privateKey);
        $entity->setWireguardPublicKey($publicKey);

        $this->wireguardHelper->method('generatePublicKey')
            ->with($privateKey)
            ->willThrowException(new SodiumException());

        $this->validator->validate($entity, new WireguardKeyPair());

        $this->buildViolation('Failed to generate the public key from the private key.')
            ->assertRaised();
    }

    #[Override]
    protected function setUp(): void
    {
        $this->wireguardHelper = $this->createMock(WireguardHelper::class);
        parent::setUp();
    }

    #[Override]
    protected function createValidator(): ConstraintValidatorInterface
    {
        return new WireguardKeyPairValidator($this->wireguardHelper);
    }
}

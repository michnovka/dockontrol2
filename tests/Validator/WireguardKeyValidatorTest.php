<?php

declare(strict_types=1);

namespace App\Tests\Validator;

use App\Validator\WireguardKey;
use App\Validator\WireguardKeyValidator;
use Generator;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @extends ConstraintValidatorTestCase<WireguardKeyValidator>
 */
class WireguardKeyValidatorTest extends ConstraintValidatorTestCase
{
    public function testNullIsValid(): void
    {
        $this->validator->validate(null, new WireguardKey('private'));

        $this->assertNoViolation();
    }

    public function testEmptyStringIsInvalid(): void
    {
        $this->validator->validate('', new WireguardKey('private'));

        $this->buildViolation('The key must be exactly 44 characters long.')
            ->assertRaised();
    }

    public function testNonStringValueIsInvalid(): void
    {
        $this->expectException(UnexpectedValueException::class);

        $this->validator->validate(123, new WireguardKey('private'));
    }

    public function testWrongConstraintType(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate('some_key', new NotBlank());
    }

    #[DataProvider('provideInvalidLengthKeys')]
    public function testInvalidLength(string $key): void
    {
        $this->validator->validate($key, new WireguardKey('private'));

        $this->buildViolation('The key must be exactly 44 characters long.')
            ->assertRaised();
    }

    public static function provideInvalidLengthKeys(): Generator
    {
        yield 'Too short' => ['abc'];
        yield 'Too long' => [str_repeat('a', 45)];
    }

    #[DataProvider('provideInvalidBase64Keys')]
    public function testInvalidBase64(string $key): void
    {
        $this->validator->validate($key, new WireguardKey('private'));

        $this->buildViolation('The key must be a valid Base64 string.')
            ->assertRaised();
    }

    public static function provideInvalidBase64Keys(): Generator
    {
        yield 'Invalid characters' => [str_repeat('!', 44)];
        yield 'Valid length but invalid Base64' => ['aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa!!!!'];
    }

    public function testInvalidDecodedLength(): void
    {
        // This is a valid Base64 string of length 44, but it decodes to less than 32 bytes
        $key = 'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA==';

        $this->validator->validate($key, new WireguardKey('private'));

        $this->buildViolation('The decoded key must be exactly 32 bytes.')
            ->assertRaised();
    }

    public function testValidKey(): void
    {
        // This is a valid Wireguard key (randomly generated for this test)
        $key = 'mNb7FqwRlP6qM8ueVYiHpdL3lIVhOSTQRNdXfzzsrGA=';

        $this->validator->validate($key, new WireguardKey('private'));

        $this->assertNoViolation();
    }

    #[Override]
    protected function createValidator(): ConstraintValidatorInterface
    {
        return new WireguardKeyValidator();
    }
}

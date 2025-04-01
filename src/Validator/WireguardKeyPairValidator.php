<?php

declare(strict_types=1);

namespace App\Validator;

use App\Entity\HasWireguardKeyPairInterface;
use App\Helper\WireguardHelper;
use Override;
use SodiumException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class WireguardKeyPairValidator extends ConstraintValidator
{
    public function __construct(private readonly WireguardHelper $wireguardHelper)
    {
    }

    #[Override]
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (null === $value) {
            return;
        }

        if (!$value instanceof HasWireguardKeyPairInterface) {
            throw new UnexpectedValueException($value, HasWireguardKeyPairInterface::class);
        }

        if (!$constraint instanceof WireguardKeyPair) {
            throw new UnexpectedTypeException($constraint, WireguardKeyPair::class);
        }

        $privateKey = $value->getWireguardPrivateKey();
        $publicKey = $value->getWireguardPublicKey();

        try {
            $generatedWireguardPublicKey = $this->wireguardHelper->generatePublicKey($privateKey);
        } catch (SodiumException) {
            $this->context->buildViolation('Failed to generate the public key from the private key.')
                ->addViolation();
            return;
        }

        if ($generatedWireguardPublicKey !== $publicKey) {
            $this->context->buildViolation('The private key does not match the public key.')
                ->addViolation();
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Validator;

use Override;
use SodiumException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class WireguardKeyValidator extends ConstraintValidator
{
    /**
     * @throws SodiumException
     */
    #[Override]
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (null === $value) {
            return;
        }

        if (!is_string($value)) {
            // throw this exception if your validator cannot handle the passed type so that it can be marked as invalid
            throw new UnexpectedValueException($value, 'string');
        }

        if (!$constraint instanceof WireguardKey) {
            throw new UnexpectedTypeException($constraint, WireguardKey::class);
        }

        // 1. Check length
        if (strlen($value) !== 44) {
            $this->context->buildViolation('The key must be exactly 44 characters long.')
                ->addViolation();
            return;
        }

        // 2. Check if it's valid Base64
        if (!preg_match('/^[A-Za-z0-9+\/]+={0,2}$/', $value)) {
            $this->context->buildViolation('The key must be a valid Base64 string.')
                ->addViolation();
            return;
        }

        // 3. Check decoded length
        try {
            $decodedKey = sodium_base642bin($value, SODIUM_BASE64_VARIANT_ORIGINAL);
            if (strlen($decodedKey) !== 32) {
                $this->context->buildViolation('The decoded key must be exactly 32 bytes.')
                    ->addViolation();
            }
        } catch (SodiumException $e) {
            $this->context->buildViolation('The key could not be decoded properly.')
                ->addViolation();
        }
    }
}

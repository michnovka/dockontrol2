<?php

declare(strict_types=1);

namespace App\Validator;

use Exception;
use Override;
use RuntimeException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class DockontrolNodeCameraPayloadValidator extends ConstraintValidator
{
    /**
     * @throws Exception
     */
    #[Override]
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof DockontrolNodeCameraPayload) {
            throw new UnexpectedValueException($value, DockontrolNodeCameraPayload::class);
        }

        if (null === $value) {
            return;
        }

        if (!is_array($value)) {
            throw new RuntimeException('Camera payload must be an array.');
        }

        $requiredFields = ['protocol', 'host', 'login', 'channel'];
        foreach ($requiredFields as $field) {
            if (empty($value[$field])) {
                $this->context->buildViolation(sprintf('The field "%s" is required and cannot be empty.', $field))
                    ->atPath('dockontrolNodePayload[' . $field . ']')
                    ->addViolation();
            }
        }
    }
}

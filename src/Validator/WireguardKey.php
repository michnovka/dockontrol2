<?php

declare(strict_types=1);

namespace App\Validator;

use Attribute;
use Override;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;

#[Attribute]
class WireguardKey extends Constraint
{
    public const string KEY_TYPE_PRIVATE = 'private';
    public const string KEY_TYPE_PUBLIC = 'public';

    public string $keyType;

    public function __construct(
        string $keyType,
        ?array $groups = null,
        mixed $payload = null,
    ) {
        if ($keyType !== self::KEY_TYPE_PRIVATE && $keyType !== self::KEY_TYPE_PUBLIC) {
            throw new ConstraintDefinitionException('Unsupported key type ' . $keyType);
        }

        parent::__construct(['value' => $keyType], $groups, $payload);
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function getDefaultOption(): string
    {
        return 'keyType';
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function getRequiredOptions(): array
    {
        return ['keyType'];
    }
}

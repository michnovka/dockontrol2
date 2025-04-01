<?php

declare(strict_types=1);

namespace App\Doctrine\ORM\TypedFieldMapper;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\TypedFieldMapper;
use Override;
use ReflectionNamedType;
use ReflectionProperty;

class EnumTypedFieldMapper implements TypedFieldMapper
{
    /**
     * {@inheritdoc}
     */
    #[Override]
    public function validateAndComplete(array $mapping, ReflectionProperty $field): array
    {
        $type = $field->getType();

        if (
            ! isset($mapping['type'])
            && ($type instanceof ReflectionNamedType)
        ) {
            if (!$type->isBuiltin() && enum_exists($type->getName())) {
                $mapping['type'] = Types::ENUM;
            }
        }

        return $mapping;
    }
}

<?php

declare(strict_types=1);

namespace App\Doctrine\DBAL\Types;

use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\Type;
use Override;

class BlobStringType extends Type
{
    /**
     * {@inheritDoc}
     */
    #[Override]
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getBlobTypeDeclarationSQL($column);
    }

    public function getName(): string
    {
        return 'blob_string';
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function getBindingType(): ParameterType
    {
        return ParameterType::LARGE_OBJECT;
    }

    /**
     * {@inheritDoc}
     **/
    #[Override]
    public function convertToPHPValue($value, AbstractPlatform $platform): mixed
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            return $value;
        }

        if (! is_resource($value)) {
            throw new ConversionException('Could not convert database value to doctrine value.');
        }

        $result = stream_get_contents($value);

        if ($result === false) {
            throw new ConversionException('Could not convert database value to doctrine value.');
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function convertToDatabaseValue($value, AbstractPlatform $platform): mixed
    {
        if (is_null($value)) {
            return null;
        }

        if (is_string($value)) {
            $value = $this->convertStringToResource($value);
        }

        return $value;
    }

    /**
     * @return resource
     */
    public function convertStringToResource(string $value)
    {
        $fp = fopen('php://temp', 'rb+');
        assert(is_resource($fp));
        fwrite($fp, $value);
        fseek($fp, 0);

        return $fp;
    }
}

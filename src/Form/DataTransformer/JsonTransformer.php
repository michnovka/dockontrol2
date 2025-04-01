<?php

declare(strict_types=1);

namespace App\Form\DataTransformer;

use Override;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class JsonTransformer implements DataTransformerInterface
{
    /**
     * @inheritDoc
     */
    #[Override]
    public function transform(mixed $value): ?string
    {
        if (is_null($value)) {
            return null;
        }

        $transformValue = json_encode($value);

        if (!is_string($transformValue)) {
            throw new TransformationFailedException('Expected a string.');
        }

        return $transformValue;
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function reverseTransform(mixed $value): ?array
    {
        if (is_null($value)) {
            return null;
        }

        if (!is_string($value)) {
            throw new TransformationFailedException('Expected a string.');
        }

        $jsonDecodeValue = json_decode($value, true);

        if (!is_array($jsonDecodeValue)) {
            throw new TransformationFailedException('Expected an array.');
        }

        return $jsonDecodeValue;
    }
}

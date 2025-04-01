<?php

declare(strict_types=1);

namespace App\Form\DataTransformer;

use Carbon\CarbonImmutable;
use DateTime;
use Override;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

/**
 * @implements DataTransformerInterface<CarbonImmutable, DateTime>
 */
final class CarbonImmutableToDateTimeTransformer implements DataTransformerInterface
{
    /**
     * Transforms a CarbonImmutable into a DateTime object.
     *
     * @param CarbonImmutable|null $value A CarbonImmutable object
     *
     * @throws TransformationFailedException If the given value is not a \DateTimeImmutable
     */
    #[Override]
    public function transform(mixed $value): ?DateTime
    {
        if (null === $value) {
            return null;
        }

        if (!$value instanceof CarbonImmutable) {
            throw new TransformationFailedException('Expected a CarbonImmutable.');
        }

        return $value->toDateTime();
    }

    /**
     * Transforms a DateTime object into a CarbonImmutable object.
     *
     * @param DateTime|null $value A DateTime object
     *
     * @throws TransformationFailedException If the given value is not a \DateTime
     */
    #[Override]
    public function reverseTransform(mixed $value): ?CarbonImmutable
    {
        if (null === $value) {
            return null;
        }

        if (!$value instanceof DateTime) {
            throw new TransformationFailedException('Expected a \DateTime.');
        }

        return CarbonImmutable::createFromMutable($value);
    }
}

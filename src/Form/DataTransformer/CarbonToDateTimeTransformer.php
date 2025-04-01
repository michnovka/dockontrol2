<?php

declare(strict_types=1);

namespace App\Form\DataTransformer;

use Carbon\Carbon;
use DateTime;
use Override;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

/**
 * @implements DataTransformerInterface<Carbon, DateTime>
 */
final class CarbonToDateTimeTransformer implements DataTransformerInterface
{
    /**
     * Transforms a Carbon into a DateTime object.
     *
     * @param Carbon|null $value A Carbon object
     *
     * @throws TransformationFailedException If the given value is not a \DateTimeImmutable
     */
    #[Override]
    public function transform(mixed $value): ?DateTime
    {
        if (null === $value) {
            return null;
        }

        if (!$value instanceof Carbon) {
            throw new TransformationFailedException('Expected a Carbon.');
        }

        return $value->toDateTime();
    }
    /**
     * Transforms a DateTime object into a Carbon object.
     *
     * @param DateTime|null $value A DateTime object
     *
     * @throws TransformationFailedException If the given value is not a \DateTime
     *
     */
    #[Override]
    public function reverseTransform(mixed $value): ?Carbon
    {
        if (null === $value) {
            return null;
        }

        if (!$value instanceof DateTime) {
            throw new TransformationFailedException('Expected a \DateTime.');
        }

        //phpcs:ignore
        return Carbon::instance($value);
    }
}

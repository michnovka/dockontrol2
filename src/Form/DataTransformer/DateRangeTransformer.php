<?php

declare(strict_types=1);

namespace App\Form\DataTransformer;

use App\Extension\Type\AbstractDateRange;
use App\Extension\Type\DateRange;
use App\Extension\Type\DateTimeRange;
use InvalidArgumentException;
use Override;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

/** @implements DataTransformerInterface<AbstractDateRange, string> */
readonly class DateRangeTransformer implements DataTransformerInterface
{
    public function __construct(
        private bool $isDateTime,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function transform($value): mixed
    {
        if (is_null($value)) {
            return null;
        }

        if (!$value instanceof DateRange && !$value instanceof DateTimeRange) {
            throw new TransformationFailedException(sprintf('Cannot convert %s type. Expected DateRange.', get_debug_type($value)));
        }

        return $value->__toString();
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function reverseTransform($value): ?AbstractDateRange
    {
        if (is_null($value)) {
            return null;
        }

        try {
            if ($this->isDateTime) {
                return DateTimeRange::fromString($value);
            } else {
                return DateRange::fromString($value);
            }
        } catch (InvalidArgumentException $e) {
            throw new TransformationFailedException(sprintf('Cannot convert %s to ' . ($this->isDateTime ? 'DateTimeRange' : 'DateRange'), $value), 0, $e);
        }
    }
}

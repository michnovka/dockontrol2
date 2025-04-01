<?php

declare(strict_types=1);

namespace App\Extension\Type;

use Carbon\CarbonImmutable;
use InvalidArgumentException;
use Override;
use Stringable;
use Throwable;

abstract readonly class AbstractDateRange implements Stringable
{
    public function __construct(
        protected CarbonImmutable $startDate,
        protected CarbonImmutable $endDate,
    ) {
        if ($startDate > $endDate) {
            throw new InvalidArgumentException(sprintf('%s cannot be start of DateTimeRange as it starts after %s', $startDate->format('Y-m-d H:i:s'), $endDate->format('Y-m-d H:i:s')));
        }
    }

    public function getStartDate(): CarbonImmutable
    {
        return $this->startDate;
    }

    public function getEndDate(): CarbonImmutable
    {
        return $this->endDate;
    }

    /**
     * @throws InvalidArgumentException
     */
    abstract public static function fromString(string $dateRangeString): self;

    /**
     * @return array{CarbonImmutable,CarbonImmutable}
     * @throws InvalidArgumentException
     */
    protected static function getStartAndEndDatesFromString(string $dateRangeString): array
    {
        $dateArray = explode(' - ', $dateRangeString, 2);

        if (count($dateArray) != 2) {
            throw new InvalidArgumentException(sprintf('%s is not a valid date range string', $dateRangeString));
        }

        $startDate = $dateArray[0];
        $endDate = $dateArray[1];

        try {
            $startDate = new CarbonImmutable($startDate);
            $endDate = new CarbonImmutable($endDate);

            if ($startDate > $endDate) {
                throw new InvalidArgumentException(sprintf('%s has start date after end date', $dateRangeString));
            }

            return [$startDate, $endDate];
        } catch (Throwable $e) {
            throw new InvalidArgumentException(sprintf('%s is not a valid date range string', $dateRangeString));
        }
    }

    #[Override]
    abstract public function __toString(): string;
}

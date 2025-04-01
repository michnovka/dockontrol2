<?php

declare(strict_types=1);

namespace App\Extension\Type;

use Carbon\CarbonImmutable;
use Override;

readonly class DateRange extends AbstractDateRange
{
    public function __construct(CarbonImmutable $startDate, CarbonImmutable $endDate)
    {
        parent::__construct(
            new CarbonImmutable($startDate->format('Y-m-d 00:00:00')),
            new CarbonImmutable($endDate->format('Y-m-d 23:59:59'))
        );
    }
    /**
     * {@inheritdoc}
     */
    #[Override]
    public static function fromString(string $dateRangeString): DateRange
    {
        return new DateRange(...self::getStartAndEndDatesFromString($dateRangeString));
    }

    public function __toString(): string
    {
        return $this->getStartDate()->format('Y-m-d') . ' - ' . $this->getEndDate()->format('Y-m-d');
    }
}

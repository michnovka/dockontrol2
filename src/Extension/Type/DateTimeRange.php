<?php

declare(strict_types=1);

namespace App\Extension\Type;

use Override;

readonly class DateTimeRange extends AbstractDateRange
{
    /**
     * {@inheritdoc}
     */
    #[Override]
    public static function fromString(string $dateRangeString): DateTimeRange
    {
        return new DateTimeRange(...self::getStartAndEndDatesFromString($dateRangeString));
    }

    public function __toString(): string
    {
        return $this->getStartDate()->format('Y-m-d H:i:s') . ' - ' . $this->getEndDate()->format('Y-m-d H:i:s');
    }
}

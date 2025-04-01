<?php

declare(strict_types=1);

namespace App\Twig\Extensions;

use Carbon\CarbonImmutable;
use Twig\Extension\RuntimeExtensionInterface;

readonly class TimeRuntime implements RuntimeExtensionInterface
{
    public function createTimeTooltip(
        ?CarbonImmutable $dateTime,
        string $textFormat = 'Y-m-d',
        string $tooltipFormat = 'Y-m-d H:i:s',
    ): string {
        if (empty($dateTime)) {
            return '<span data-bs-toggle="tooltip" data-bs-original-title="N/A">N/A</span>';
        } else {
            $html = '<span data-bs-toggle="tooltip" data-bs-original-title="' . $dateTime->format($tooltipFormat) . '">';
            $html .= $dateTime->format($textFormat);
            $html .= '</span>';
            return $html;
        }
    }

    public function formatUptime(int $timestamp): string
    {
        $years = floor($timestamp / (365 * 24 * 3600));
        $days = floor(($timestamp % (365 * 24 * 3600)) / (24 * 3600));
        $hours = floor(($timestamp % (24 * 3600)) / 3600);
        $minutes = floor(($timestamp % 3600) / 60);
        $seconds = $timestamp % 60;

        $timeAgoString = '';

        if ($years > 0) {
            $timeAgoString .= "$years year" . ($years > 1 ? 's' : '');
        }

        if ($days > 0) {
            if ($timeAgoString) {
                $timeAgoString .= ', ';
            }
            $timeAgoString .= "$days day" . ($days > 1 ? 's' : '');
        }

        if ($hours > 0) {
            if ($timeAgoString) {
                $timeAgoString .= ', ';
            }
            $timeAgoString .= "$hours hour" . ($hours > 1 ? 's' : '');
        }

        if ($minutes > 0) {
            if ($timeAgoString) {
                $timeAgoString .= ', ';
            }
            $timeAgoString .= "$minutes minute" . ($minutes > 1 ? 's' : '');
        }

        if ($seconds > 0 || $timeAgoString === '') {
            if ($timeAgoString) {
                $timeAgoString .= ', ';
            }
            $timeAgoString .= "$seconds second" . ($seconds > 1 ? 's' : '');
        }

        return $timeAgoString;
    }


    public function dockontrolNodeLastPingTime(?float $ping, ?CarbonImmutable $lastPingTime): string
    {
        if (!empty($ping) && !empty($lastPingTime)) {
            $html = '<span data-bs-toggle="tooltip" data-bs-original-title="' . $lastPingTime->format('Y-m-d H:i:s') . '">';
            $html .= number_format($ping, 3) . ' ms';
            $html .= '</span>';
        } else {
            $html = '<span data-bs-toggle="tooltip" data-bs-original-title="N/A">N/A</span>';
        }

        return $html;
    }

    public function uptimeSince(?int $uptimeInSeconds): string
    {
        if (!is_null($uptimeInSeconds)) {
            $uptimeHours = floor($uptimeInSeconds / 3600);
            $currentDateTime = CarbonImmutable::now();
            $uptimeStart = $currentDateTime->subHours($uptimeHours);

            $tooltipHtml = '<span data-bs-toggle="tooltip" data-bs-original-title="' . $uptimeStart->format('Y-m-d H:i:s') . '">';
            $tooltipHtml .= (string) $uptimeHours . ' Hours';
            $tooltipHtml .= '</span>';

            return $tooltipHtml;
        }

        return '<span data-bs-toggle="tooltip" data-bs-original-title="Never">Never</span>';
    }
}

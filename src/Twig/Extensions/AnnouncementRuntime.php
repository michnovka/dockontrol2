<?php

declare(strict_types=1);

namespace App\Twig\Extensions;

use App\Entity\Announcement;
use Carbon\CarbonImmutable;
use Twig\Extension\RuntimeExtensionInterface;

class AnnouncementRuntime implements RuntimeExtensionInterface
{
    public function getAnnouncementVisibilityBadge(Announcement $announcement, bool $showTooltip = false): string
    {
        $currentDate = CarbonImmutable::now();
        $announcementStartTime = $announcement->getStartTime();
        $announcementEndTime = $announcement->getEndTime();
        $badge = '';

        if (!$announcementStartTime && !$announcementEndTime) {
            $badge = '<span class="badge bg-secondary">Always</span>';
        } else {
            $tooltip = '';
            if ($showTooltip) {
                $tooltipText = 'N/A';
                if ($announcementStartTime && $announcementEndTime) {
                    $tooltipText = "From: " . $announcementStartTime->format('Y-m-d H:i:s') . " To: " . $announcementEndTime->format('Y-m-d H:i:s');
                } elseif ($announcementStartTime) {
                    $tooltipText = "From: " . $announcementStartTime->format('Y-m-d H:i:s') . " To: N/A";
                } elseif ($announcementEndTime) {
                    $tooltipText = "From: N/A To: " . $announcementEndTime->format('Y-m-d H:i:s');
                }
                $tooltip = sprintf(' data-bs-toggle="tooltip" title="%s"', $tooltipText);
            }

            if ($announcementStartTime && $announcementEndTime) {
                if ($currentDate->between($announcementStartTime, $announcementEndTime)) {
                    $badge = sprintf('<span class="badge bg-success"%s>Shown</span>', $tooltip);
                } elseif ($currentDate->lt($announcementStartTime)) {
                    $badge = sprintf('<span class="badge bg-primary"%s>Planned</span>', $tooltip);
                } else {
                    $badge = sprintf('<span class="badge bg-danger"%s>Expired</span>', $tooltip);
                }
            } elseif ($announcementStartTime) {
                $badge = $currentDate->lt($announcementStartTime)
                    ? sprintf('<span class="badge bg-primary"%s>Planned</span>', $tooltip)
                    : sprintf('<span class="badge bg-success"%s>Shown</span>', $tooltip);
            } elseif ($announcementEndTime) {
                $badge = $currentDate->gt($announcementEndTime)
                    ? sprintf('<span class="badge bg-danger"%s>Expired</span>', $tooltip)
                    : sprintf('<span class="badge bg-success"%s>Shown</span>', $tooltip);
            }
        }

        return $badge;
    }
}

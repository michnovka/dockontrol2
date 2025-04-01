<?php

declare(strict_types=1);

namespace App\Twig\Extensions;

use App\Entity\ActionQueue;
use App\Entity\Enum\ActionQueueStatus;
use Twig\Extension\RuntimeExtensionInterface;

class ActionQueueRuntime implements RuntimeExtensionInterface
{
    public function getActionQueueStatusBadge(ActionQueue $actionQueue): string
    {
        $status = $actionQueue->getStatus();

        $badgeClass = match ($status) {
            ActionQueueStatus::EXECUTED => 'bg-success',
            ActionQueueStatus::FAILED => 'bg-danger',
            ActionQueueStatus::QUEUED => 'bg-primary',
        };

        return '<span class="badge rounded-pill ' . $badgeClass . '">' . $status->getReadable() . '</span>';
    }
}

<?php

declare(strict_types=1);

namespace App\Twig\Extensions;

use App\Entity\DockontrolNode;
use App\Entity\Enum\DockontrolNodeStatus;
use Carbon\CarbonImmutable;
use Twig\Extension\RuntimeExtensionInterface;

class DockontrolNodeRuntime implements RuntimeExtensionInterface
{
    public function showDockontrolNodeStatusIndicator(DockontrolNode $dockontrolNode): string
    {
        $lastPingTime = $dockontrolNode->getLastPingTime();
        $tooltipText = sprintf(
            'Status: %s, Last Ping Time: %s, Fail count: %d',
            $dockontrolNode->isEnabled() ? $dockontrolNode->getStatus()->getReadable() : 'Disabled',
            $lastPingTime instanceof CarbonImmutable ? $lastPingTime->format('Y-m-d H:i:s') : 'Never',
            $dockontrolNode->getFailCount()
        );

        $iconClass = $dockontrolNode->getStatus() === DockontrolNodeStatus::ONLINE
            ? 'bi bi-check-circle text-success'
            : 'bi bi-x-circle text-danger';

        return sprintf(
            '<span class="cursor-pointer" data-bs-toggle="tooltip" data-bs-original-title="%s">
            <i class="%s"></i>
        </span>',
            $tooltipText,
            $iconClass
        );
    }

    public function showDockontrolNodeStatusBadge(DockontrolNode $dockontrolNode): string
    {
        $dockontrolNodeStatus = $dockontrolNode->getStatus();

        if (!$dockontrolNode->isEnabled()) {
            return '<span class="badge rounded-pill bg-secondary">DISABLED</span>';
        }

        $dockontrolNodeStatus = $dockontrolNode->getStatus();
        $tooltipText = sprintf('Fail count: %d', $dockontrolNode->getFailCount());

        $statusColors = [
            DockontrolNodeStatus::ONLINE->value => 'bg-success',
            DockontrolNodeStatus::PINGABLE->value => 'bg-info',
            DockontrolNodeStatus::OFFLINE->value => 'bg-warning',
            DockontrolNodeStatus::INVALID_API_SECRET->value => 'bg-danger',
        ];

        $badgeColor = $statusColors[$dockontrolNodeStatus->value];

        return sprintf(
            '<span class="badge rounded-pill %s" data-bs-toggle="tooltip" data-bs-original-title="%s">%s</span>',
            $badgeColor,
            $tooltipText,
            $dockontrolNodeStatus->getReadable()
        );
    }
}

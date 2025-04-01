<?php

declare(strict_types=1);

namespace App\Entity\Enum;

use Elao\Enum\Attribute\EnumCase;
use Elao\Enum\ExtrasTrait;
use Elao\Enum\ReadableEnumInterface;
use Elao\Enum\ReadableEnumTrait;

enum ActionType: string implements ReadableEnumInterface
{
    use ReadableEnumTrait;
    use ExtrasTrait;

    #[EnumCase('DOCKontrol Node Relay', extras: ['badge_class' => 'border-indigo'])]
    case DOCKONTROL_NODE_RELAY = 'dockontrol_node_relay';

    #[EnumCase('Multi', extras: ['badge_class' => 'border-teal'])]
    case MULTI = 'multi';

    public function getBadgeClass(): string
    {
        return $this->getExtra('badge_class', true);
    }
}

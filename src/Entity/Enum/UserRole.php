<?php

declare(strict_types=1);

namespace App\Entity\Enum;

use Elao\Enum\Attribute\EnumCase;
use Elao\Enum\ExtrasTrait;
use Elao\Enum\ReadableEnumInterface;
use Elao\Enum\ReadableEnumTrait;

enum UserRole: string implements ReadableEnumInterface
{
    use ReadableEnumTrait;
    use ExtrasTrait;

    #[EnumCase('Admin', extras: ['badge_class' => 'bg-teal bg-opacity-20 text-teal'])]
    case ADMIN = 'ROLE_ADMIN';

    #[EnumCase('Super Admin', extras: ['badge_class' => 'bg-success bg-opacity-20 text-success'])]
    case SUPER_ADMIN = 'ROLE_SUPER_ADMIN';

    #[EnumCase('Landlord', extras: ['badge_class' => 'bg-primary bg-opacity-20 text-primary'])]
    case LANDLORD = 'ROLE_LANDLORD';

    #[EnumCase('Tenant', extras: ['badge_class' => 'bg-info bg-opacity-20 text-info'])]
    case TENANT = 'ROLE_TENANT';

    public function getBadgeClass(): string
    {
        return $this->getExtra('badge_class', true);
    }
}

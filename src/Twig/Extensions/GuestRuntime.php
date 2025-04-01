<?php

declare(strict_types=1);

namespace App\Twig\Extensions;

use App\Entity\Guest;
use Carbon\CarbonImmutable;
use Twig\Extension\RuntimeExtensionInterface;

readonly class GuestRuntime implements RuntimeExtensionInterface
{
    private const string VALID = 'Valid';
    private const string EXPIRED = 'Expired';
    private const string OVERUSED = 'Overused';
    private const string DISABLED = 'Disabled';

    public function getGuestStatusBadge(Guest $guest): string
    {
        $status = match (true) {
            !$guest->isEnabled() => self::DISABLED,
            $guest->getExpires() < CarbonImmutable::now() => self::EXPIRED,
            $guest->getRemainingActions() === 0 => self::OVERUSED,
            default => self::VALID,
        };

        $badgeClass = match ($status) {
            self::VALID => 'bg-success',
            self::EXPIRED => 'bg-primary',
            self::OVERUSED => 'bg-warning',
            self::DISABLED => 'bg-danger',
        };

        return '<span class="badge rounded-pill ' . $badgeClass . '">' . $status . '</span>';
    }
}

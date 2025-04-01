<?php

declare(strict_types=1);

namespace App\Twig\Extensions;

use App\Entity\Log\EmailChangeLog;
use Twig\Extension\RuntimeExtensionInterface;

class EmailChangeLogRuntime implements RuntimeExtensionInterface
{
    public function getEmailChangeLogStatus(EmailChangeLog $emailChangeLog): string
    {
        $timeCreated = $emailChangeLog->getTimeCreated();
        $expiryTime = $timeCreated->addHour();
        $isExpired = $expiryTime->isPast();

        $oldEmailConfirmed = $emailChangeLog->getOldEmailConfirmedTime();
        $newEmailConfirmed = $emailChangeLog->getNewEmailConfirmedTime();

        if ($oldEmailConfirmed && $newEmailConfirmed) {
            return '<span class="badge bg-success">FINISHED</span>';
        }

        if ($isExpired) {
            return '<span class="badge bg-danger">EXPIRED</span>';
        }

        if ($oldEmailConfirmed || $newEmailConfirmed) {
            return '<span class="badge bg-info text-dark">INITIATED</span>';
        }

        return '<span class="badge bg-warning">PENDING</span>';
    }
}

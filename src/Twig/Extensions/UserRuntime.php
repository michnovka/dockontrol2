<?php

declare(strict_types=1);

namespace App\Twig\Extensions;

use Carbon\CarbonImmutable;
use Twig\Extension\RuntimeExtensionInterface;

readonly class UserRuntime implements RuntimeExtensionInterface
{
    public function showVerifyEmailLink(?CarbonImmutable $lastEmailSentTime): string
    {
        $currentDateTime = CarbonImmutable::now();
        $diffInMinutes = intval($lastEmailSentTime?->diffInMinutes($currentDateTime));

        if ($lastEmailSentTime instanceof CarbonImmutable && $diffInMinutes < 5) {
            return '<a href="#" class="alert-link" >Please Wait ' . (5 - $diffInMinutes) . ' minutes before trying again</a>';
        } else {
            return '<a href="#" id="send-verification-mail" class="alert-link">Click here to verify your e-mail.</a>';
        }
    }
}

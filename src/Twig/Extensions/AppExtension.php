<?php

declare(strict_types=1);

namespace App\Twig\Extensions;

use Override;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{
    /**
     * {@inheritdoc}
     */
    #[Override]
    public function getFilters(): array
    {
        return [
            new TwigFilter('time_tooltip', [TimeRuntime::class, 'createTimeTooltip'], [
                'is_safe' => ['html'],
            ]),
            new TwigFilter('uptime_since', [TimeRuntime::class, 'uptimeSince'], [
                'is_safe' => ['html'],
            ]),
            new TwigFilter('format_uptime', [TimeRuntime::class, 'formatUptime'], [
                'is_safe' => ['html'],
            ]),
            new TwigFilter('guest_status_badge', [GuestRuntime::class, 'getGuestStatusBadge'], [
                'is_safe' => ['html'],
            ]),
            new TwigFilter('dockontrol_node_last_ping_time', [TimeRuntime::class, 'dockontrolNodeLastPingTime'], [
                'is_safe' => ['html'],
            ]),
            new TwigFilter('action_queue_status_badge', [ActionQueueRuntime::class, 'getActionQueueStatusBadge'], [
                'is_safe' => ['html'],
            ]),
            new TwigFilter('cron_tab', [CronRuntime::class, 'getCronTab'], [
                'is_safe' => ['html'],
            ]),
            new TwigFilter('dockontrol_node_status_indicator', [DockontrolNodeRuntime::class, 'showDockontrolNodeStatusIndicator'], [
                'is_safe' => ['html'],
            ]),
            new TwigFilter('show_verification_link', [UserRuntime::class, 'showVerifyEmailLink'], [
                'is_safe' => ['html'],
            ]),
            new TwigFilter('email_change_log_status', [EmailChangeLogRuntime::class, 'getEmailChangeLogStatus'], [
                'is_safe' => ['html'],
            ]),
            new TwigFilter('dockontrol_node_status_badge', [DockontrolNodeRuntime::class, 'showDockontrolNodeStatusBadge'], [
                'is_safe' => ['html'],
            ]),
            new TwigFilter('translate_button_text', [ButtonRuntime::class, 'translateButtonText'], [
                'is_safe' => ['html'],
            ]),
            new TwigFilter('is_button_text_translatable', [ButtonRuntime::class, 'isButtonTextTranslatable'], [
                'is_safe' => ['html'],
            ]),
            new TwigFilter('announcement_visibility_badge', [AnnouncementRuntime::class, 'getAnnouncementVisibilityBadge'], [
                'is_safe' => ['html'],
            ]),
        ];
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction('enum', [EnumRuntime::class, 'createProxy']),
        ];
    }
}

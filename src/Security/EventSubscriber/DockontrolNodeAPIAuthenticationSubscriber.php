<?php

declare(strict_types=1);

namespace App\Security\EventSubscriber;

use App\Entity\Log\ApiCallFailedLog\DockontrolNodeAPICallFailedLog;
use Override;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;

readonly class DockontrolNodeAPIAuthenticationSubscriber extends AbstractAPIAuthenticatorSubscriber
{
    protected const array FIREWALLS = ['dockontrol_node_api'];

    #[Override]
    public function onLoginFailure(LoginFailureEvent $event): void
    {
        $firewallName = $event->getFirewallName();

        if (!in_array($firewallName, self::FIREWALLS)) {
            return;
        }

        $apiCallFailedLog = new DockontrolNodeAPICallFailedLog();
        $apiCallFailedLog->setDockontrolNodeAPIKey($this->getUserIdentifier($event));

        $this->logLoginFailure($event, $apiCallFailedLog);
    }
}

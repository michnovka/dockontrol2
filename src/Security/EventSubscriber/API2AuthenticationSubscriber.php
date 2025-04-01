<?php

declare(strict_types=1);

namespace App\Security\EventSubscriber;

use App\Entity\Log\ApiCallFailedLog\API2CallFailedLog;
use Override;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;

readonly class API2AuthenticationSubscriber extends AbstractAPIAuthenticatorSubscriber
{
    protected const array FIREWALLS = ['api2'];

    #[Override]
    public function onLoginFailure(LoginFailureEvent $event): void
    {
        $firewallName = $event->getFirewallName();

        if (!in_array($firewallName, self::FIREWALLS)) {
            return;
        }

        $apiCallFailedLog = new API2CallFailedLog();
        $apiCallFailedLog->setApiKey($this->getUserIdentifier($event));

        $this->logLoginFailure($event, $apiCallFailedLog);
    }
}

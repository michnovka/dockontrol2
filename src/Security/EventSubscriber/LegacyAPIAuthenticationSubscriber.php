<?php

declare(strict_types=1);

namespace App\Security\EventSubscriber;

use App\Entity\Log\ApiCallFailedLog\LegacyAPICallFailedLog;
use Override;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;

readonly class LegacyAPIAuthenticationSubscriber extends AbstractAPIAuthenticatorSubscriber
{
    protected const array FIREWALLS = ['api1'];

    #[Override]
    public function onLoginFailure(LoginFailureEvent $event): void
    {
        $firewallName = $event->getFirewallName();

        if (!in_array($firewallName, self::FIREWALLS)) {
            return;
        }

        $apiCallFailedLog = new LegacyAPICallFailedLog();
        $apiCallFailedLog->setEmail($this->getUserIdentifier($event));
        $apiCallFailedLog->setApiAction((string) $this->getRequestValue($event->getRequest(), 'action'));

        $this->logLoginFailure($event, $apiCallFailedLog);
    }

    public function getRequestValue(Request $request, string $key): mixed
    {
        $value = null;
        if ($request->query->has($key)) {
            $value = $request->query->get($key);
        } elseif ($request->request->has($key)) {
            $value = $request->request->get($key);
        }

        return $value;
    }
}

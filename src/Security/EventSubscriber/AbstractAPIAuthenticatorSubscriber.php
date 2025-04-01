<?php

declare(strict_types=1);

namespace App\Security\EventSubscriber;

use App\Entity\Log\ApiCallFailedLog\AbstractApiCallFailedLog;
use App\Helper\ApiActionHelper;
use Carbon\CarbonImmutable;
use Override;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;

abstract readonly class AbstractAPIAuthenticatorSubscriber implements EventSubscriberInterface
{
    protected const array FIREWALLS = [];

    public function __construct(
        private LoggerInterface $logger,
        private ApiActionHelper $apiActionHelper,
    ) {
    }

    /**
     * @inheritdoc
     */
    #[Override]
    public static function getSubscribedEvents(): array
    {
        return [
            LoginFailureEvent::class => 'onLoginFailure',
        ];
    }

    abstract public function onLoginFailure(LoginFailureEvent $event): void;

    protected function logLoginFailure(LoginFailureEvent $event, AbstractApiCallFailedLog $apiCallFailedLog): void
    {
        $firewallName = $event->getFirewallName();

        if (!in_array($firewallName, static::FIREWALLS)) {
            return;
        }

        $authenticationException = $event->getException();
        $userIdentifier = $event->getPassport()?->getBadge(UserBadge::class)->getUserIdentifier();
        $request = $event->getRequest();

        $ipAddress = $request->getClientIp() ?? '';

        $this->apiActionHelper->logAPICallFailed(
            $apiCallFailedLog,
            $ipAddress,
            $request->getPathInfo(),
            'unauthorized'
        );

        // Log the failed login attempt
        $this->logger->warning('API call failed.', [
            'email' => $userIdentifier,
            'firewall' => $firewallName,
            'time' => (CarbonImmutable::now())->format(DATE_ATOM),
            'error' => $authenticationException->getMessageData(),
        ]);
    }

    protected function getUserIdentifier(LoginFailureEvent $event): string
    {
        return $event->getPassport()?->getBadge(UserBadge::class)->getUserIdentifier();
    }
}

<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\User;
use App\Helper\UserActionLogHelper;
use Exception;
use Override;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\SwitchUserToken;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Event\SwitchUserEvent;
use Symfony\Component\Security\Http\SecurityEvents;

readonly class SwitchUserSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private UserActionLogHelper $userActionLogHelper,
        private TokenStorageInterface $tokenStorage,
    ) {
    }

    /**
     * @return array{'security.switch_user': 'onSwitchUser'}
     */
    #[Override]
    public static function getSubscribedEvents(): array
    {
        return [
            SecurityEvents::SWITCH_USER => 'onSwitchUser',
        ];
    }

    /**
     * @throws Exception
     */
    public function onSwitchUser(SwitchUserEvent $event): void
    {
        $token = $this->tokenStorage->getToken();

        if (empty($token)) {
            throw new Exception("Cannot impersonate user without valid token.");
        }

        /** @var User $currentUser  */
        $currentUser = $token->getUser();

        /** @var User $targetUser*/
        $targetUser = $event->getTargetUser();

        if ($currentUser === $targetUser) {
            throw new AccessDeniedException('You can not impersonate your own account.');
        }

        if (!$targetUser->isEnabled()) {
            throw new AccessDeniedException('Cannot impersonate a disabled user.');
        }

        if (!($token instanceof SwitchUserToken)) {
            $this->userActionLogHelper->addUserActionLog('Impersonated ' . $targetUser->getEmail(), $currentUser);
        }
    }
}

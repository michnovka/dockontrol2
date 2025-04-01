<?php

declare(strict_types=1);

namespace App\Security\EventSubscriber;

use App\Security\Credentials\APIKeyPairCredentials;
use Override;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\CheckPassportEvent;

class CheckAPIKeyPairCredentialsSubscriber implements EventSubscriberInterface
{
    public function checkPassport(CheckPassportEvent $event): void
    {
        $passport = $event->getPassport();

        if ($passport->hasBadge(APIKeyPairCredentials::class)) {
            /** @var APIKeyPairCredentials $badge */
            $badge = $passport->getBadge(APIKeyPairCredentials::class);
            if ($badge->isResolved()) {
                return;
            }

            $badge->validate($passport->getUser());

            return;
        }
    }

    /**
     * @inheritdoc
     */
    #[Override]
    public static function getSubscribedEvents(): array
    {
        return [CheckPassportEvent::class => 'checkPassport'];
    }
}

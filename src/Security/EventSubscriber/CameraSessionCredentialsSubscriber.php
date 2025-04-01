<?php

declare(strict_types=1);

namespace App\Security\EventSubscriber;

use App\Entity\Camera;
use App\Entity\User;
use App\Repository\CameraRepository;
use App\Security\Credentials\CameraSessionCredentials;
use Override;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Event\CheckPassportEvent;

readonly class CameraSessionCredentialsSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private CameraRepository $cameraRepository,
    ) {
    }

    /**
     * @inheritdoc
     */
    #[Override]
    public static function getSubscribedEvents(): array
    {
        return [CheckPassportEvent::class => 'checkPassport'];
    }

    public function checkPassport(CheckPassportEvent $event): void
    {
        $passport = $event->getPassport();

        if ($passport->hasBadge(CameraSessionCredentials::class)) {
            /** @var CameraSessionCredentials $badge */
            $badge = $passport->getBadge(CameraSessionCredentials::class);

            if ($badge->isResolved()) {
                return;
            }

            $this->validate($passport->getUser(), $badge);
        }
    }

    public function validate(UserInterface $user, CameraSessionCredentials $badge): void
    {
        $currentCameraObj = $this->cameraRepository->find($badge->getCameraId());

        if (!$user instanceof User) {
            throw new AuthenticationException('Camera session expired.');
        }

        if (!$currentCameraObj instanceof Camera) {
            throw new AuthenticationException('Camera not found.');
        }

        $cameraSessionData = $user->getCameraSessionData();

        if (empty($cameraSessionData)) {
            throw new AuthenticationException('Camera session expired.');
        }

        foreach ($cameraSessionData->cameras as $camera) {
            if ($camera->getNameId() === $currentCameraObj->getNameId()) {
                $badge->markAsResolved();
                break;
            }
        }
    }
}

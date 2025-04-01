<?php

declare(strict_types=1);

namespace App\Security\UserProvider;

use App\DTO\CameraSessionData;
use App\Entity\User;
use App\Helper\CameraHelper;
use App\Repository\UserRepository;
use Override;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Throwable;

/**
 * @implements UserProviderInterface<User>
 */
readonly class CameraSessionIDUserProvider implements UserProviderInterface
{
    public function __construct(
        private CameraHelper $cameraHelper,
        private UserRepository $userRepository,
    ) {
    }

    #[Override]
    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        try {
            /** @var CameraSessionData $cameraSessionData*/
            $cameraSessionData = $this->cameraHelper->getCameraSession($identifier);

            if (empty($cameraSessionData)) {
                throw new UserNotFoundException('Camera session expired.');
            }

            if (empty($cameraSessionData->cameras) || empty($cameraSessionData->userId)) {
                throw new UserNotFoundException('Camera session expired.');
            }

            $user = $this->userRepository->find($cameraSessionData->userId);

            if (!$user instanceof User) {
                throw new UserNotFoundException('User not found.');
            }

            $user->setCameraSessionData($cameraSessionData);

            return $user;
        } catch (Throwable) {
            throw new UserNotFoundException('Camera session expired.');
        }
    }

    #[Override]
    public function supportsClass(string $class): bool
    {
        return $class === User::class || is_subclass_of($class, User::class);
    }

    #[Override]
    public function refreshUser(UserInterface $user): UserInterface
    {
        throw new UnsupportedUserException('Stateless authentication does not support refreshing users.');
    }
}

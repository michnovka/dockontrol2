<?php

declare(strict_types=1);

namespace App\Security\UserProvider;

use App\Entity\User;
use App\Repository\APIKeyRepository;
use Doctrine\Persistence\ManagerRegistry;
use Override;
use Symfony\Bridge\Doctrine\Security\User\EntityUserProvider;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Uuid;
use Throwable;

/**
 * @extends EntityUserProvider<User>
 */
class APIKeyPairUserProvider extends EntityUserProvider
{
    public function __construct(private readonly APIKeyRepository $apiKeyRepository, ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, User::class, 'email');
    }

    #[Override]
    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        // Load APIKey by pubkey
        try {
            $identifierUuid = Uuid::fromString($identifier);

            $apiKey = $this->apiKeyRepository->findOneBy(['publicKey' => $identifierUuid]);

            if (!$apiKey) {
                throw new UserNotFoundException('API Key not found.');
            }

            $user = $apiKey->getUser();

            $user->setCurrentApiKey($apiKey);

            return $user;
        } catch (Throwable) {
            throw new UserNotFoundException('API Key not found.');
        }
    }

    #[Override]
    public function supportsClass(string $class): bool
    {
        return $class === User::class || is_subclass_of($class, User::class);
    }
}

<?php

declare(strict_types=1);

namespace App\Security\UserProvider;

use App\Entity\DockontrolNode;
use App\Repository\DockontrolNodeRepository;
use Doctrine\Persistence\ManagerRegistry;
use Override;
use Symfony\Bridge\Doctrine\Security\User\EntityUserProvider;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Uuid;
use Throwable;

/**
 * @extends EntityUserProvider<DockontrolNode>
 */
class APIKeyPairDockontrolNodeProvider extends EntityUserProvider
{
    public function __construct(
        private readonly DockontrolNodeRepository $dockontrolNodeRepository,
        ManagerRegistry $managerRegistry,
    ) {
        parent::__construct($managerRegistry, DockontrolNode::class, 'apiPublicKey');
    }

    #[Override]
    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        // Load APIKey by pubkey
        try {
            $identifierUuid = Uuid::fromString($identifier);

            $dockontrolNode = $this->dockontrolNodeRepository->findOneBy(['apiPublicKey' => $identifierUuid]);

            if (!$dockontrolNode) {
                throw new UserNotFoundException('API Key not found.');
            }

            return $dockontrolNode;
        } catch (Throwable) {
            throw new UserNotFoundException('API Key not found.');
        }
    }

    #[Override]
    public function supportsClass(string $class): bool
    {
        return $class === DockontrolNode::class || is_subclass_of($class, DockontrolNode::class);
    }
}

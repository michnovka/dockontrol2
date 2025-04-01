<?php

declare(strict_types=1);

namespace App\Helper;

use App\Entity\APIKey;
use App\Repository\APIKeyRepository;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;

readonly class APIKeyHelper
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private APIKeyRepository $apiKeyRepository,
    ) {
    }

    public function saveAPIKey(APIKey $apiKey): void
    {
        $userHasReachedApiKeyLimit = $this->apiKeyRepository->hasReachedApiKeyLimit($apiKey->getUser());

        if ($userHasReachedApiKeyLimit) {
            throw new RuntimeException('Max limit reached for API keys.');
        }

        $this->entityManager->persist($apiKey);
        $this->entityManager->flush();
    }

    public function deleteAPIKey(APIKey $apiKey): void
    {
        $this->entityManager->remove($apiKey);
        $this->entityManager->flush();
    }
}

<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Doctrine\ORM\EntityManager;
use Override;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class BaseControllerTestCase extends WebTestCase
{
    private KernelBrowser $client;

    private EntityManager $entityManager;

    /**
     * @param class-string $entityName
     */
    protected function getLogCount(string $entityName): int
    {
        $queryBuilder = $this->entityManager->createQueryBuilder();
        $queryBuilder->select('COUNT(entity)')
            ->from($entityName, 'entity');

        return (int) $queryBuilder->getQuery()->getSingleScalarResult();
    }

    /**
     * @param class-string $entityName
     */
    protected function getLatestLogEntry(string $entityName): mixed
    {
        $queryBuilder = $this->entityManager->createQueryBuilder();
        $queryBuilder->select('entity')
            ->from($entityName, 'entity')
        ->orderBy('entity.time', 'DESC')
        ->setMaxResults(1);

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }


    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();
        $this->entityManager = self::getContainer()->get('doctrine.orm.entity_manager');
    }

    #[Override]
    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
    }

    /**
     * @param array<string, string> $headers
     * @param array<string, string> $parameters
     */
    protected function makeRequest(
        string $method,
        string $endpoint,
        array $headers = [],
        ?string $body = null,
        array $parameters = [],
    ): void {
        $this->client->request($method, $endpoint, $parameters, [], $headers, $body);
    }

    protected function getResponseContent(): false|string
    {
        return $this->client->getResponse()->getContent();
    }

    /**
     * @template T of object
     * @param class-string<T> $entityName
     */
    protected function truncateTable(string $entityName): void
    {
        $connection = $this->entityManager->getConnection();
        /** @psalm-suppress InvalidArgument */
        $classMetadata = $this->entityManager->getClassMetadata($entityName);
        $tableName = $classMetadata->getTableName();

        $connection->executeStatement('SET FOREIGN_KEY_CHECKS=0');
        $connection->executeStatement('TRUNCATE TABLE ' . $tableName);
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS=1');
    }
}

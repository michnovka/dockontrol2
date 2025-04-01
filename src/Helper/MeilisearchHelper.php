<?php

declare(strict_types=1);

namespace App\Helper;

use Doctrine\ORM\EntityManagerInterface;
use Meilisearch\Bundle\SearchService;
use RuntimeException;

readonly class MeilisearchHelper
{
    public const string RESULT_KEY_HITS = 'hits';
    public const string RESULT_KEY_OBJECTID = 'objectID';

    public function __construct(private EntityManagerInterface $entityManager, private SearchService $searchService)
    {
    }

    /**
     * @template T of object
     * @param class-string<T> $className
     * @param array<string, string> $sort
     * @param string[]|null $filter
     * @return array{data: T[], hasNextPage: bool}
     */
    public function searchAndHydrate(
        string $className,
        string $query,
        int $limit,
        array $sort,
        int $page = 1,
        ?array $filter = null,
    ): array {
        $results = [];
        $arrayOfIDs = [];
        /** @var string $currentValueOfSort*/
        $currentValueOfSort = current($sort);

        $rawResult = $this->searchService->rawSearch($className, $query, searchParams: [
            'hitsPerPage' => $limit,
            'page' => $page,
            'filter' => $filter,
            'sort' => [key($sort) . ':' . $currentValueOfSort],
        ]);

        if (!array_key_exists(self::RESULT_KEY_HITS, $rawResult)) {
            throw new RuntimeException('There is no hits key in the search results.');
        }

        foreach ($rawResult[self::RESULT_KEY_HITS] as $hit) {
            if (!array_key_exists(self::RESULT_KEY_OBJECTID, $hit)) {
                throw new RuntimeException('There is an object id in the search result.');
            }

            $objectID = $hit[self::RESULT_KEY_OBJECTID];
            $arrayOfIDs[] = $objectID;
        }

        $searchResult = $this->fetchAndOrderEntities($className, $arrayOfIDs);

        $results['data'] = $searchResult;
        $results['hasNextPage'] = $rawResult['page'] !== $rawResult['totalPages'];

        return $results;
    }

    /**
     * @template T of object
     * @param class-string<T> $entityClass
     * @param int[] $sortedArrayOfIds
     * @return T[]
     */
    private function fetchAndOrderEntities(
        string $entityClass,
        array $sortedArrayOfIds,
    ): array {
        $metadata = $this->entityManager->getClassMetadata($entityClass);

        $identifierFieldNames = $metadata->getIdentifier();
        if (count($identifierFieldNames) !== 1) {
            throw new RuntimeException("Only entities with a single identifier are supported.");
        }
        $idFieldName = $identifierFieldNames[0];

        $repository = $this->entityManager->getRepository($entityClass);
        $entities = $repository->findBy([$idFieldName => $sortedArrayOfIds]);

        $entityMap = [];
        foreach ($entities as $entity) {
            $idValues = $metadata->getIdentifierValues($entity);
            $idValue = $idValues[$idFieldName];
            $entityMap[$idValue] = $entity;
        }

        $sortedEntities = [];
        foreach ($sortedArrayOfIds as $id) {
            if (isset($entityMap[$id])) {
                $sortedEntities[] = $entityMap[$id];
            }
        }

        return $sortedEntities;
    }
}

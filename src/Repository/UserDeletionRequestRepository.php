<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\UserDeletionRequest;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserDeletionRequest>
 */
class UserDeletionRequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserDeletionRequest::class);
    }

    public function getQueryBuilder(): Query
    {
        return $this->createQueryBuilder('udr')
            ->getQuery();
    }

    /**
     * @return UserDeletionRequest[]
     */
    public function getLastFiveUserDeletionRequest(): array
    {
        return $this->createQueryBuilder('udr')
            ->orderBy('udr.time', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();
    }
}

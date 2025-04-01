<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Group;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Group>
 */
class GroupRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Group::class);
    }

    public function getQueryBuilder(): Query
    {
        return $this->createQueryBuilder('g')
            ->select('g as group')
            ->leftJoin('g.users', 'u')
            ->addSelect('count(u) as users')
            ->leftJoin('g.buildings', 'b')
            ->addSelect('COUNT(DISTINCT b.id) as buildings')
            ->groupBy('g.id')
            ->getQuery();
    }
}

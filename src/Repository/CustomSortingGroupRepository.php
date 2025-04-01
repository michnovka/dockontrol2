<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\CustomSortingGroup;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CustomSortingGroup>
 */
class CustomSortingGroupRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CustomSortingGroup::class);
    }

    public function deleteCustomSortingGroupForUser(User $user): void
    {
        $this->createQueryBuilder('csg')
            ->delete(CustomSortingGroup::class, 'csg')
            ->where('csg.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();
    }
}

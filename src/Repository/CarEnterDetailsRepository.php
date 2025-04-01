<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Building;
use App\Entity\CarEnterDetails;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CarEnterDetails>
 */
class CarEnterDetailsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CarEnterDetails::class);
    }

    public function getMaxOrderNumber(User|Building $userOrBuildingObject): int
    {
        $queryBuilder = $this->createQueryBuilder('ced')
            ->select('COALESCE(MAX(ced.order), 0) as max_order');

        if ($userOrBuildingObject  instanceof User) {
            $queryBuilder->where('ced.user = :entity');
        } else {
            $queryBuilder->where('ced.building = :entity');
        }

        return (int) $queryBuilder
            ->setParameter('entity', $userOrBuildingObject)
            ->getQuery()
            ->setLockMode(LockMode::PESSIMISTIC_WRITE)
            ->getSingleScalarResult();
    }

    /**
     * @return array<CarEnterDetails>
     */
    public function getUsersCarEnterDetails(User $user, bool $isExit = false): array
    {
        $qb = $this->createQueryBuilder('ced')
            ->innerJoin('ced.user', 'u')
            ->where('u.id = :userId')
            ->setParameter('userId', $user->getId());

        if ($isExit) {
            $qb->orderBy('ced.order', 'DESC');
        } else {
            $qb->orderBy('ced.order', 'ASC');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @return array<CarEnterDetails>
     */
    public function getBuildingsCarEnterDetails(Building $building, bool $isExit = false): array
    {
        $qb = $this->createQueryBuilder('ced')
            ->innerJoin('ced.building', 'b')
            ->where('b.id = :buildingId')
            ->setParameter('buildingId', $building->getId());

        if ($isExit) {
            $qb->orderBy('ced.order', 'DESC');
        } else {
            $qb->orderBy('ced.order', 'ASC');
        }

        return $qb->getQuery()->getResult();
    }
}

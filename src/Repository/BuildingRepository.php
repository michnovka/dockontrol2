<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Building;
use App\Entity\Enum\UserRole;
use App\Entity\Group;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;
use InvalidArgumentException;

/**
 * @extends ServiceEntityRepository<Building>
 *
 * @psalm-type BuildingFilterArr = array{
 *     name: ?string,
 *     defaultGroup: ?Group,
 * }
 */
class BuildingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Building::class);
    }

    /**
     * @psalm-param BuildingFilterArr $filterArr
     */
    public function getQueryBuilder(array $filterArr): Query
    {
        $qb = $this->createQueryBuilder('b')
            ->leftJoin('b.defaultGroup', 'g')
            ->leftJoin('b.apartments', 'a')
            ->select('b as building')
            ->addSelect('g, COUNT(a) as apartments');

        if (!empty($filterArr['name'])) {
            $qb->andWhere('b.name LIKE :name')
                ->setParameter('name', '%' . $filterArr['name'] . '%');
        }

        if (!empty($filterArr['defaultGroup'])) {
            $qb->andWhere('b.defaultGroup = :defaultGroup')
                ->setParameter('defaultGroup', $filterArr['defaultGroup']);
        }

        return $qb->groupBy('b')
            ->getQuery();
    }

    /**
     * @return Building[]|null
     */
    public function searchBuilding(string $searchText, User $adminUser): ?array
    {
        $qb = $this->createQueryBuilder('b');
        $terms = explode(' ', $searchText);

        foreach ($terms as $index => $term) {
            $qb->orWhere('b.name LIKE :name_' . $index)
               ->setParameter('name_' . $index, '%' . $term . '%');
        }

        $userRole = $adminUser->getRole();

        if ($userRole === UserRole::SUPER_ADMIN) {
            // can access all buildings
        } elseif ($userRole === UserRole::ADMIN) {
            $qb->andWhere('b IN (:buildings)')
                ->setParameter('buildings', $adminUser->getAdminBuildings());
        } else {
            throw new InvalidArgumentException('Unsupported user role');
        }

        return $qb->getQuery()->getResult();
    }
}

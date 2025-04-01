<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Apartment;
use App\Entity\Building;
use App\Entity\Enum\UserRole;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;
use InvalidArgumentException;

/**
 * @extends ServiceEntityRepository<Apartment>
 *
 * @psalm-type ApartmentFilterArray = array{
 *     name: ?string,
 *     building: ?Building
 * }
 */
class ApartmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Apartment::class);
    }

    /**
     * @psalm-param ApartmentFilterArray $filter
     */
    public function getQueryBuilderWithUserCount(array $filter): Query
    {
        $qb = $this->createQueryBuilder('a')
            ->select('a as apartment')
            ->leftJoin('a.building', 'b')
            ->leftJoin('a.defaultGroup', 'g')
            ->leftJoin('a.users', 'u')
            ->addSelect('b, g')
            ->addSelect('count(u) as users')
        ->groupBy('a.id');

        if (!empty($filter['name'])) {
            $qb->andWhere('a.name LIKE :name')
                ->setParameter('name', $filter['name']);
        }

        if (!empty($filter['building'])) {
            $qb->andWhere('b = :building')
                ->setParameter('building', $filter['building']);
        }

        return $qb->getQuery();
    }



    /**
     *@return Apartment[]
     */
    public function searchApartment(string $searchText, User $user): array
    {
        $terms = explode(' ', $searchText);
        $qb = $this->createQueryBuilder('a')
            ->leftJoin('a.building', 'b');

        foreach ($terms as $index => $term) {
            $qb->orWhere('a.name LIKE :name_' . $index)
                ->orWhere('b.name LIKE :buildingName_' . $index)
                ->setParameter('name_' . $index, '%' . $term . '%')
                ->setParameter('buildingName_' . $index, '%' . $term . '%');
        }

        if ($user->getRole() === UserRole::SUPER_ADMIN) {
            //allow all apartments
        } elseif ($user->getRole() === UserRole::ADMIN) {
            $qb->andWhere('a.building IN (:buildings)')
                ->setParameter('buildings', $user->getAdminBuildings());
        } else {
            throw new InvalidArgumentException('Invalid user role.');
        }

        return $qb->getQuery()->getResult();
    }
}

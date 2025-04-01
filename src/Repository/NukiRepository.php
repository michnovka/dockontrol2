<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Enum\UserRole;
use App\Entity\Nuki;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;
use InvalidArgumentException;

/**
 * @extends ServiceEntityRepository<Nuki>
 * @psalm-type NukiFilterArray = array{
 *     user: ?User,
 *     name: ?string
 * }
 */
class NukiRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Nuki::class);
    }

    /**
     * @psalm-param NukiFilterArray $filter
     */
    public function getQueryBuilder(User $adminUser, array $filter): Query
    {
        $qb = $this->createQueryBuilder('n')
            ->innerJoin('n.user', 'u')
            ->addSelect('u');

        if (!empty($filter['user'])) {
            $qb->andWhere('u = :user')
                ->setParameter('user', $filter['user']);
        }

        if (!empty($filter['name'])) {
            $qb->andWhere('n.name LIKE :name')
                ->setParameter('name', '%' . $filter['name'] . '%');
        }

        if ($adminUser->getRole() === UserRole::SUPER_ADMIN) {
            // can access all nukis
        } elseif ($adminUser->getRole() === UserRole::ADMIN) {
            $qb
                ->innerJoin('u.apartment', 'a')
                ->andWhere('a.building IN (:adminBuildings)')
                ->setParameter('adminBuildings', $adminUser->getAdminBuildings());
        } else {
            throw new InvalidArgumentException('Invalid User Role');
        }

        return $qb->getQuery();
    }
}

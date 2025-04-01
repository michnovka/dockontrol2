<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Button;
use App\Entity\Enum\UserRole;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;
use InvalidArgumentException;

/**
 * @extends ServiceEntityRepository<Button>
 */
class ButtonRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Button::class);
    }

    public function getQueryBuilder(): Query
    {
        return $this->createQueryBuilder('b')
            ->innerJoin('b.action', 'a')
            ->leftJoin('b.permission', 'p')
            ->getQuery();
    }

    /**
     * @return array<Button>
     */
    public function getUserButtons(User $user, bool $prefetchPermissions = true, bool $fullView = false): array
    {
        $queryBuilder = $this->createQueryBuilder('b')
            ->innerJoin('b.permission', 'p')
            ->innerJoin('p.groups', 'g')
            ->innerJoin('g.users', 'u');

        if ($prefetchPermissions) {
            $queryBuilder->addSelect('p');
        }
        if ($fullView) {
            if ($user->getRole() === UserRole::SUPER_ADMIN) {
                //  show all buttons, no permission check required
            } elseif ($user->getRole() === UserRole::ADMIN) {
                $groups = $user->getGroups();
                foreach ($user->getAdminBuildings() as $adminBuilding) {
                    $groups->add($adminBuilding->getDefaultGroup());
                }

                $queryBuilder
                    ->innerJoin('u.adminBuildings', 'ab')
                    ->where('g IN (:groups)')
                    ->setParameter('groups', $groups);
            } else {
                throw new InvalidArgumentException('Invalid user role.');
            }
        } else {
            $queryBuilder
                ->where('u = :user')
                ->setParameter('user', $user);
        }

        $queryBuilder->orderBy('b.sortIndex')
        ->addOrderBy('b.name')
        ->addOrderBy('b.nameSpecification');
        return $queryBuilder
            ->getQuery()
            ->getResult();
    }

    public function getTotalUserButtonCount(User $user): int
    {
        return (int) $this->createQueryBuilder('b')
            ->select('COUNT(DISTINCT b.id)')
            ->innerJoin('b.permission', 'p')
            ->innerJoin('p.groups', 'g')
            ->innerJoin('g.users', 'u')
            ->andWhere('u = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }
}

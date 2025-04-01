<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Enum\UserRole;
use App\Entity\Guest;
use App\Entity\User;
use Carbon\CarbonImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;
use InvalidArgumentException;

/**
 * @extends ServiceEntityRepository<Guest>
 *
 * @psalm-type GuestFilterArray = array{
 *     user: ?User,
 *     enabled: ?bool
 *  }
 */
class GuestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Guest::class);
    }

    /**
     * @return Guest[]|null
     */
    public function getGuestPassForUser(User $user): ?array
    {
        return $this->createQueryBuilder('g')
            ->where('g.user = :user')
            ->andWhere('g.remainingActions > 0 OR g.remainingActions = -1')
            ->andWhere('g.enabled = :enabled')
            ->andWhere('g.expires >= :now')
            ->setParameter('user', $user)
            ->setParameter('enabled', true)
            ->setParameter('now', CarbonImmutable::now())
            ->orderBy('g.created', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /** @psalm-param GuestFilterArray $filter */
    public function getQueryBuilder($filter, User $adminUser): Query
    {
        $qb = $this->createQueryBuilder('g')
                ->innerJoin('g.user', 'u')
                ->innerJoin('u.apartment', 'a')
                ->addSelect('u')
                ->addSelect('a');

        if (!empty($filter['user'])) {
            $qb->where('g.user = :user')
                ->setParameter('user', $filter['user']);
        }

        if (isset($filter['enabled'])) {
            $qb->andWhere('g.enabled = :enabled')
                ->setParameter('enabled', $filter['enabled']);
        }

        if ($adminUser->getRole() === UserRole::SUPER_ADMIN) {
        } elseif ($adminUser->getRole() === UserRole::ADMIN) {
            $qb
                ->andWhere('a.building in (:adminBuilding)')
                ->setParameter('adminBuilding', $adminUser->getAdminBuildings());
        } else {
            throw new InvalidArgumentException('Invalid user role.');
        }

        return $qb->getQuery();
    }

    public function cleanupGuestPasses(int $intervalDays): void
    {
        $this->createQueryBuilder('g')
            ->delete(Guest::class, 'g')
            ->where('g.expires <= DATE_SUB(CURRENT_TIMESTAMP(), :interval, \'DAY\')')
            ->setParameter('interval', $intervalDays)
            ->getQuery()
            ->execute();
    }
}

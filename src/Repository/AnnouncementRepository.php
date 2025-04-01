<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Announcement;
use App\Entity\Enum\UserRole;
use App\Entity\User;
use Carbon\CarbonImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use InvalidArgumentException;

/**
 * @extends ServiceEntityRepository<Announcement>
 */
class AnnouncementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Announcement::class);
    }

    /**
     * @return null|array<Announcement>
     */
    public function getAnnouncementsForAdmin(User $user, bool $onlyActive = false): ?array
    {
        $qb = $this->createQueryBuilder('a');

        if ($onlyActive) {
            $qb->where('(a.startTime IS NULL OR a.startTime <= :now)')
                ->andWhere('(a.endTime IS NULL OR a.endTime >= :now)')
                ->setParameter('now', CarbonImmutable::now());
        }

        $role = $user->getRole();

        if ($role === UserRole::SUPER_ADMIN) {
            // allow all announcements
        } elseif ($role === UserRole::ADMIN) {
            $qb->andWhere('a.building IS NULL OR a.building IN (:adminBuildings)')
                ->setParameter('adminBuildings', $user->getAdminBuildings());
        } else {
            throw new InvalidArgumentException('Invalid user role.');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @return null|array<Announcement>
     */
    public function getActiveAnnouncementsForUser(User $user): ?array
    {
        return $this->createQueryBuilder('a')
            ->where('(a.startTime IS NULL OR a.startTime <= :now)')
            ->andWhere('(a.endTime IS NULL OR a.endTime >= :now)')
            ->setParameter('now', CarbonImmutable::now())
            ->andWhere('a.building IS NULL OR a.building = :userBuildings')
            ->setParameter('userBuildings', $user->getApartment()->getBuilding())
            ->getQuery()
            ->getResult();
    }

    public function cleanupAnnouncements(int $intervalDays): void
    {
        $this->createQueryBuilder('a')
            ->delete(Announcement::class, 'a')
            ->where('a.endTime < DATE_SUB(now(), :interval, \'DAY\')')
            ->andWhere('a.endTime IS NOT NULL')
            ->setParameter('interval', $intervalDays)
            ->getQuery()
            ->execute()
        ;
    }
}

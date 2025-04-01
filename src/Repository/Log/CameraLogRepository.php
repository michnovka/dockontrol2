<?php

declare(strict_types=1);

namespace App\Repository\Log;

use App\Entity\Camera;
use App\Entity\Log\CameraLog;
use App\Entity\User;
use App\Extension\Type\DateRange;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CameraLog>
 *
 * @psalm-type CameraLogFilterArray = array{
 *     time: ?DateRange,
 *     user: ?User,
 *     camera: ?Camera
 * }
 */
class CameraLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CameraLog::class);
    }

    /**
     * @psalm-param CameraLogFilterArray $filter
     */
    public function getQueryBuilder(array $filter): Query
    {
        $qb =  $this->createQueryBuilder('cl');

        if (!empty($filter['time'])) {
            $qb->andWhere('cl.time BETWEEN :start AND :end')
                ->setParameter('start', $filter['time']->getStartDate()->format('Y-m-d H:i:s'))
                ->setParameter('end', $filter['time']->getEndDate()->format('Y-m-d H:i:s'));
        }

        if (!empty($filter['user'])) {
            $qb->innerJoin('cl.user', 'u')
                ->andWhere('u = :user')
                ->setParameter('user', $filter['user']);
        }

        if (!empty($filter['camera'])) {
            $qb->innerJoin('cl.camera', 'c')
                ->andWhere('c = :camera')
                ->setParameter('camera', $filter['camera']);
        }

        return $qb->getQuery();
    }

    public function cleanupLogs(int $intervalDays): void
    {
        $this->createQueryBuilder('cl')
            ->delete(CameraLog::class, 'cl')
            ->where('cl.time < DATE_SUB(now(), :interval, \'DAY\')')
            ->setParameter('interval', $intervalDays)
            ->getQuery()
            ->execute()
        ;
    }

    /**
     * @return CameraLog[]
     */
    public function getCameraLogsForUser(User $user, ?int $limit = null): iterable
    {
        $qb = $this->createQueryBuilder('cl')
            ->where('cl.user = :user')
            ->setParameter('user', $user)
            ->orderBy('cl.time', 'DESC');

        if ($limit) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->toIterable();
    }
}

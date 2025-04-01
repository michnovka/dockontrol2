<?php

declare(strict_types=1);

namespace App\Repository\Log;

use App\Entity\Enum\NukiAction;
use App\Entity\Enum\NukiStatus;
use App\Entity\Log\NukiLog;
use App\Entity\Nuki;
use App\Entity\User;
use App\Extension\Type\DateRange;
use Carbon\CarbonImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<NukiLog>
 *
 * @psalm-type NukiLogFilterArray = array{
 *     time: ?DateRange,
 *     user: ?User,
 *     nuki: ?Nuki,
 *     status: ?NukiStatus,
 *     action: ?NukiAction
 * }
 */
class NukiLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NukiLog::class);
    }

    /**
     * @psalm-param NukiLogFilterArray $filter
     */
    public function getQueryBuilder(array $filter): Query
    {
        $qb = $this->createQueryBuilder('nl')
            ->innerJoin('nl.nuki', 'n');

        if (!empty($filter['time'])) {
            $qb->andWhere('nl.time BETWEEN :start AND :end')
                ->setParameter('start', $filter['time']->getStartDate()->format('Y-m-d H:i:s'))
                ->setParameter('end', $filter['time']->getEndDate()->format('Y-m-d H:i:s'));
        }

        if (!empty($filter['user'])) {
            $qb
                ->innerJoin('n.user', 'u')
                ->andWhere('n.user = :user')
                ->setParameter('user', $filter['user']);
        }

        if (!empty($filter['nuki'])) {
            $qb->andWhere('n = :nuki')
                ->setParameter('nuki', $filter['nuki']);
        }

        if (!empty($filter['status'])) {
            $qb->andWhere('nl.status = :status')
                ->setParameter('status', $filter['status']);
        }

        if (!empty($filter['action'])) {
            $qb->andWhere('nl.action = :action')
                ->setParameter('action', $filter['action']);
        }

        return $qb->getQuery();
    }

    public function cleanupLogs(int $intervalDays): void
    {
        $this->createQueryBuilder('nl')
            ->delete(NukiLog::class, 'nl')
            ->where('nl.time < DATE_SUB(now(), :interval, \'DAY\')')
            ->setParameter('interval', $intervalDays)
            ->getQuery()
            ->execute()
        ;
    }

    public function getIncorrectStatusTriesCountForNukiForPastOneMinute(Nuki $nuki, NukiStatus $status): int
    {
        $time = CarbonImmutable::now()->subMinute();
        return (int) $this->createQueryBuilder('log')
                ->select('COUNT(log.id)')
                ->where('log.nuki = :nuki')
                ->andWhere('log.status = :status')
                ->andWhere('log.time > :time')
                ->setParameter('nuki', $nuki)
                ->setParameter('time', $time)
                ->setParameter('status', $status)
                ->getQuery()
                ->getSingleScalarResult();
    }
}

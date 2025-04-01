<?php

/** @noinspection ALL */

declare(strict_types=1);

namespace App\Repository\Log\ApiCallLog;

use App\Entity\Action;
use App\Entity\Log\ApiCallLog\LegacyAPICallLog;
use App\Entity\User;
use App\Extension\Type\DateRange;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LegacyAPICallLog>
 *
 *  @psalm-type LegacyApiCallFilterArray = array{
 *        time: ?DateRange,
 *        user: ?User,
 *        ip: ?string,
 *       apiAction: ?Action
 *  }
 */
class LegacyAPICallLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LegacyAPICallLog::class);
    }

    /**
     * @psalm-param LegacyApiCallFilterArray $filter
     */
    public function getQueryBuilder(array $filter): Query
    {
        $qb = $this->createQueryBuilder('lal');

        if (!empty($filter['time'])) {
            $qb->andWhere('lal.time BETWEEN :start AND :end')
                ->setParameter('start', $filter['time']->getStartDate()->format('Y-m-d H:i:s'))
                ->setParameter('end', $filter['time']->getEndDate()->format('Y-m-d H:i:s'));
        }

        if (!empty($filter['user'])) {
            $qb
                ->innerJoin('lal.user', 'u')
                ->andWhere('u = :user')
                ->setParameter('user', $filter['user']);
        }

        if (!empty($filter['ip'])) {
            $qb->andWhere('lal.ip = :ip')
                ->setParameter('ip', $filter['ip']);
        }

        if (!empty($filter['apiAction'])) {
            $qb
                ->andWhere('lal.apiAction = :apiAction')
                ->setParameter('apiAction', $filter['apiAction']->getName());
        }

        return $qb->getQuery();
    }

    public function cleanupApiCallLogs(int $intervalDays): void
    {
        $this->createQueryBuilder('wr')
            ->delete(LegacyAPICallLog::class, 'lal')
            ->where('lal.time < DATE_SUB(now(), :interval, \'DAY\')')
            ->setParameter('interval', $intervalDays)
            ->getQuery()
            ->execute()
        ;
    }
}

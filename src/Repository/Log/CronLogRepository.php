<?php

declare(strict_types=1);

namespace App\Repository\Log;

use App\Entity\ActionQueueCronGroup;
use App\Entity\Enum\CronType;
use App\Entity\Log\CronLog;
use App\Extension\Type\DateRange;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CronLog>
 * @psalm-type CronLogFilterArray = array{
 *   timeStart: ?DateRange,
 *   timeEnd: ?DateRange,
 *   cronGroup: ?ActionQueueCronGroup,
 *   cronType: ?string
 * }
 */
class CronLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CronLog::class);
    }

    /**
     * @psalm-param CronLogFilterArray $filter
     */
    public function getQueryBuilder(array $filter): Query
    {
        $qb = $this->createQueryBuilder('cl')
        ->leftJoin('cl.actionQueueCronGroup', 'cg');

        if (!empty($filter['timeStart'])) {
            $qb->andWhere('cl.timeEnd BETWEEN :start AND :end')
                ->setParameter('start', $filter['timeStart']->getStartDate()->format('Y-m-d H:i:s'))
                ->setParameter('end', $filter['timeStart']->getEndDate()->format('Y-m-d H:i:s'));
        }

        if (!empty($filter['timeEnd'])) {
            $qb->andWhere('cl.timeEnd BETWEEN :start AND :end')
                ->setParameter('start', $filter['timeEnd']->getStartDate()->format('Y-m-d H:i:s'))
                ->setParameter('end', $filter['timeEnd']->getEndDate()->format('Y-m-d H:i:s'));
        }

        if (!empty($filter['cronGroup'])) {
            $qb->andWhere('cg = :cronGroup')
                ->setParameter('cronGroup', $filter['cronGroup']);
        }

        if (!empty($filter['cronType'])) {
            $qb->andWhere('cl.type = :cronType')
                ->setParameter('cronType', $filter['cronType']);
        }

        $qb->orderBy('cl.timeStart', 'DESC')
        ->addOrderBy('cl.timeEnd', 'DESC');

        return $qb->getQuery();
    }

    public function cleanupLogs(int $intervalDays): void
    {
        $this->createQueryBuilder('cl')
            ->delete()
            ->where('cl.timeStart < DATE_SUB(now(), :interval, \'DAY\')')
            ->setParameter('interval', $intervalDays)
            ->getQuery()
            ->execute()
        ;
    }

    /**
     * @return array<int, array{type: CronType, lastRun: string}>
     */
    public function getLastSuccessfulRunForEachCronType(): array
    {
        return $this->createQueryBuilder('cl')
            ->select('cl.type, DATE_FORMAT(MAX(cl.timeEnd), \'%Y-%m-%d %H:%i:%s\') as lastRun')
            ->where('cl.success = :success')
            ->andWhere('cl.type != :type')
            ->setParameter('type', CronType::ACTION_QUEUE)
            ->setParameter('success', true)
            ->groupBy('cl.type')
            ->getQuery()
            ->getResult();
    }
}

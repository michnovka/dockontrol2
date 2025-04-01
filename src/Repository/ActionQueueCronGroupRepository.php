<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ActionQueueCronGroup;
use App\Entity\Enum\ActionQueueStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ActionQueueCronGroup>
 */
class ActionQueueCronGroupRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ActionQueueCronGroup::class);
    }

    public function getQueryBuilder(): Query
    {
        return $this->createQueryBuilder('aqcg')
            ->select(
                'aqcg.name',
                'COUNT(DISTINCT a) as actions',
                'COUNT(aq.id) as actionQueues',
                'SUM(CASE WHEN aq.isImmediate = 0 THEN 1 ELSE 0 END) as scheduledActions',
                'SUM(CASE WHEN aq.isImmediate = 1 THEN 1 ELSE 0 END) as immediateActions'
            )
            ->leftJoin('aqcg.actions', 'a')
            ->leftJoin('a.actionQueues', 'aq', 'WITH', 'aq.status = :status')
            ->setParameter('status', ActionQueueStatus::QUEUED)
            ->groupBy('aqcg.name')
            ->getQuery();
    }

    /**
     * @return array<int, array{name: string, lastRun: ?string}>
     */
    public function getLastRunTimesByCronGroup(): array
    {
        return $this->createQueryBuilder('aqcg')
            ->select("aqcg.name, DATE_FORMAT(MAX(cl.timeEnd), '%Y-%m-%d %H:%i:%s') as lastRun")
            ->leftJoin('aqcg.cronLogs', 'cl', 'WITH', 'cl.success = true')
            ->groupBy('aqcg.name')
            ->orderBy('aqcg.name', 'ASC')
            ->addOrderBy('lastRun', 'DESC')
            ->getQuery()
            ->getResult();
    }
}

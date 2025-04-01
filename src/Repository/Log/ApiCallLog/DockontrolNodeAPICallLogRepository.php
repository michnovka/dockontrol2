<?php

declare(strict_types=1);

namespace App\Repository\Log\ApiCallLog;

use App\Entity\DockontrolNode;
use App\Entity\Log\ApiCallLog\DockontrolNodeAPICallLog;
use App\Extension\Type\DateRange;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DockontrolNodeAPICallLog>
 *
 * @psalm-type DockontrolNodeApiCallFilterArray = array{
 *     time: ?DateRange,
 *     ip: ?string,
 *     dockontrolNode: ?DockontrolNode
 *   }
 */
class DockontrolNodeAPICallLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DockontrolNodeAPICallLog::class);
    }

    /**
     * @psalm-param DockontrolNodeApiCallFilterArray $filter
     */
    public function getQueryBuilder(array $filter): Query
    {
        $qb = $this->createQueryBuilder('dnal');

        if (!empty($filter['time'])) {
            $qb->andWhere('dnal.time BETWEEN :start AND :end')
                ->setParameter('start', $filter['time']->getStartDate()->format('Y-m-d H:i:s'))
                ->setParameter('end', $filter['time']->getEndDate()->format('Y-m-d H:i:s'));
        }

        if (!empty($filter['ip'])) {
            $qb->andWhere('dnal.ip = :ip')
                ->setParameter('ip', $filter['ip']);
        }

        if (!empty($filter['dockontrolNode'])) {
            $qb->andWhere('dnal.dockontrolNode = :dockControlNode')
                ->setParameter('dockControlNode', $filter['dockontrolNode']);
        }

        return $qb->getQuery();
    }
}

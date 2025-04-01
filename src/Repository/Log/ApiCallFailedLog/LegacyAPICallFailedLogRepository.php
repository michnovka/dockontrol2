<?php

declare(strict_types=1);

namespace App\Repository\Log\ApiCallFailedLog;

use App\Entity\Log\ApiCallFailedLog\LegacyAPICallFailedLog;
use App\Entity\Log\ApiCallLog\LegacyAPICallLog;
use App\Repository\Log\ApiCallLog\LegacyAPICallLogRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LegacyAPICallLog>
 * @psalm-import-type LegacyApiCallFilterArray from LegacyAPICallLogRepository
 */
class LegacyAPICallFailedLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LegacyAPICallFailedLog::class);
    }

    /**
     * @psalm-param LegacyApiCallFilterArray $filter
     */
    public function getQueryBuilder(array $filter): Query
    {
        $qb = $this->createQueryBuilder('lafl');

        if (!empty($filter['time'])) {
            $qb->andWhere('lafl.time BETWEEN :start AND :end')
                ->setParameter('start', $filter['time']->getStartDate()->format('Y-m-d H:i:s'))
                ->setParameter('end', $filter['time']->getEndDate()->format('Y-m-d H:i:s'));
        }

        if (!empty($filter['user'])) {
            $qb
                ->andWhere('lafl.email = :email')
                ->setParameter('email', $filter['user']->getEmail());
        }

        if (!empty($filter['ip'])) {
            $qb->andWhere('lafl.ip = :ip')
                ->setParameter('ip', $filter['ip']);
        }

        if (!empty($filter['apiAction'])) {
            $qb
                ->andWhere('lafl.apiAction = :apiAction')
                ->setParameter('apiAction', $filter['apiAction']->getName());
        }

        return $qb->getQuery();
    }
}

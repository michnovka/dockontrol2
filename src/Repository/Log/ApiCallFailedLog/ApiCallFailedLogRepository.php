<?php

declare(strict_types=1);

namespace App\Repository\Log\ApiCallFailedLog;

use App\Entity\Log\ApiCallFailedLog\AbstractApiCallFailedLog;
use App\Repository\Log\ApiCallLog\LegacyAPICallLogRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AbstractApiCallFailedLog>
 * @psalm-import-type LegacyApiCallFilterArray from LegacyAPICallLogRepository
 */
class ApiCallFailedLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AbstractApiCallFailedLog::class);
    }

    public function cleanupFailedApiCallLogs(int $intervalDays): void
    {
        $this->createQueryBuilder('wr')
            ->delete(AbstractApiCallFailedLog::class, 'acl')
            ->where('acl.time < DATE_SUB(now(), :interval, \'DAY\')')
            ->setParameter('interval', $intervalDays)
            ->getQuery()
            ->execute()
        ;
    }
}

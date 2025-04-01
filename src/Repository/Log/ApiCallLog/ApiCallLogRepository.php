<?php

declare(strict_types=1);

namespace App\Repository\Log\ApiCallLog;

use App\Entity\Log\ApiCallLog\AbstractApiCallLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AbstractApiCallLog>
 */
class ApiCallLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AbstractApiCallLog::class);
    }
}

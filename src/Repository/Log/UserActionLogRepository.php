<?php

declare(strict_types=1);

namespace App\Repository\Log;

use App\Entity\Log\UserActionLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserActionLog>
 */
class UserActionLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserActionLog::class);
    }
}

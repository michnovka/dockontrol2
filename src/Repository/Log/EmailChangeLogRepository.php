<?php

declare(strict_types=1);

namespace App\Repository\Log;

use App\Entity\Log\EmailChangeLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EmailChangeLog>
 */
class EmailChangeLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EmailChangeLog::class);
    }

    public function getQueryBuilder(): Query
    {
        return $this->createQueryBuilder('ecl')
            ->getQuery();
    }

    public function cleanupLogs(int $intervalDays): void
    {
        $this->createQueryBuilder('ecl')
            ->delete(EmailChangeLog::class, 'ecl')
            ->where('ecl.timeCreated < DATE_SUB(now(), :interval, \'DAY\')')
            ->setParameter('interval', $intervalDays)
            ->getQuery()
            ->execute()
        ;
    }
}

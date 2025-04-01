<?php

declare(strict_types=1);

namespace App\Repository\Log;

use App\Entity\Log\EmailLog;
use App\Extension\Type\DateRange;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EmailLog>
 * @psalm-type EmailLogFilterArray = array{
 *      time: ?DateRange
 * }
 */
class EmailLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EmailLog::class);
    }

    /**
     * @psalm-param EmailLogFilterArray $filter
     */
    public function getQueryBuilder(array $filter = []): Query
    {
        $qb = $this->createQueryBuilder('el');

        if (!empty($filter['time'])) {
            $qb->andWhere('el.time BETWEEN :start AND :end')
                ->setParameter('start', $filter['time']->getStartDate()->format('Y-m-d H:i:s'))
                ->setParameter('end', $filter['time']->getEndDate()->format('Y-m-d H:i:s'));
        }

        return $qb->getQuery();
    }

    public function cleanupLogs(int $intervalDays): void
    {
        $this->createQueryBuilder('el')
            ->delete(EmailLog::class, 'el')
            ->where('el.time < DATE_SUB(now(), :interval, \'DAY\')')
            ->setParameter('interval', $intervalDays)
            ->getQuery()
            ->execute()
        ;
    }
}

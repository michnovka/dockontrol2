<?php

declare(strict_types=1);

namespace App\Repository\Log;

use App\Entity\Log\LoginLog;
use App\Entity\User;
use App\Extension\Type\DateRange;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LoginLog>
 *
 *  @psalm-type LoginLogFilterArray = array{
 *      time: ?DateRange,
 *      user: null|User|string,
 *      ip: ?string,
 *      browser: ?string,
 *      plaform: ?string,
 *  }
 */
class LoginLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LoginLog::class);
    }

    /**
     * @psalm-param LoginLogFilterArray $filter
     */
    public function getQueryBuilder(array $filter): Query
    {
        $qb = $this->createQueryBuilder('l');

        if (!empty($filter['time'])) {
            $qb->andWhere('l.time BETWEEN :start AND :end')
                ->setParameter('start', $filter['time']->getStartDate()->format('Y-m-d H:i:s'))
                ->setParameter('end', $filter['time']->getEndDate()->format('Y-m-d H:i:s'));
        }

        if (!empty($filter['user'])) {
            $qb
                ->innerJoin('l.user', 'u')
                ->andWhere('u = :user')
                ->setParameter('user', $filter['user']);
        }

        if (!empty($filter['ip'])) {
            $qb->andWhere('l.ip = :ip')
                ->setParameter('ip', $filter['ip']);
        }

        if (!empty($filter['browser'])) {
            $qb->andWhere('l.browser LIKE :browser')
                ->setParameter('browser', '%' . $filter['browser'] . '%');
        }

        if (!empty($filter['platform'])) {
            $qb->andWhere('l.platform LIKE :platform')
                ->setParameter('platform', '%' . $filter['platform'] . '%');
        }

        return $qb->getQuery();
    }

    public function cleanupLogs(int $intervalDays): void
    {
        $this->createQueryBuilder('ll')
            ->delete(LoginLog::class, 'll')
            ->where('ll.time < date_sub(now(), :interval, \'DAY\')')
            ->setParameter('interval', $intervalDays)
            ->getQuery()
            ->execute()
        ;
    }
}

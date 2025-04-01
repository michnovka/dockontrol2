<?php

declare(strict_types=1);

namespace App\Repository\Log;

use App\Entity\Log\LoginLogFailed;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LoginLogFailed>
 * @psalm-import-type LoginLogFilterArray from LoginLogRepository
 */
class LoginLogFailedRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LoginLogFailed::class);
    }

    /**
     * @psalm-param LoginLogFilterArray $filter
     */
    public function getQueryBuilder(array $filter): Query
    {
        $qb = $this->createQueryBuilder('lf');

        if (!empty($filter['time'])) {
            $qb->andWhere('lf.time BETWEEN :start AND :end')
                ->setParameter('start', $filter['time']->getStartDate()->format('Y-m-d H:i:s'))
                ->setParameter('end', $filter['time']->getEndDate()->format('Y-m-d H:i:s'));
        }

        if (!empty($filter['user'])) {
            $qb->andWhere('lf.email LIKE :email')
                ->setParameter('email', '%' . $filter['user'] . '%');
        }

        if (!empty($filter['ip'])) {
            $qb->andWhere('lf.ip = :ip')
                ->setParameter('ip', $filter['ip']);
        }

        if (!empty($filter['browser'])) {
            $qb->andWhere('lf.browser LIKE :browser')
                ->setParameter('browser', '%' . $filter['browser'] . '%');
        }

        if (!empty($filter['platform'])) {
            $qb->andWhere('lf.platform LIKE :platform')
                ->setParameter('platform', '%' . $filter['platform'] . '%');
        }

        return $qb->getQuery();
    }

    public function cleanupLogs(int $intervalDays): void
    {
        $this->createQueryBuilder('llf')
            ->delete(LoginLogFailed::class, 'llf')
            ->where('llf.time < DATE_SUB(now(), :interval, \'DAY\')')
            ->setParameter('interval', $intervalDays)
            ->getQuery()
            ->execute()
        ;
    }
}

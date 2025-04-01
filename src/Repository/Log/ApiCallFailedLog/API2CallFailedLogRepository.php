<?php

declare(strict_types=1);

namespace App\Repository\Log\ApiCallFailedLog;

use App\Entity\Log\ApiCallFailedLog\API2CallFailedLog;
use App\Extension\Type\DateRange;
use Carbon\CarbonImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<API2CallFailedLog>
 *
 *    @psalm-type Api2FailedCallFilterArray = array{
 *       time: ?DateRange,
 *       apiEndpoint: ?string,
 *       ip: ?string,
 *       apiKey: ?string
 *    }
 */
class API2CallFailedLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, API2CallFailedLog::class);
    }

    /**
     * @psalm-param Api2FailedCallFilterArray $filter
     */
    public function getQueryBuilder(array $filter): Query
    {
        $qb =  $this->createQueryBuilder('vfl');

        if (!empty($filter['time'])) {
            $qb->andWhere('vfl.time BETWEEN :start AND :end')
                ->setParameter('start', $filter['time']->getStartDate()->format('Y-m-d H:i:s'))
                ->setParameter('end', $filter['time']->getEndDate()->format('Y-m-d H:i:s'));
        }

        if (!empty($filter['ip'])) {
            $qb->andWhere('vfl.ip = :ip')
                ->setParameter('ip', $filter['ip']);
        }

        if (!empty($filter['apiEndpoint'])) {
            $qb->andWhere('vfl.apiEndpoint = :apiEndpoint')
                ->setParameter('apiEndpoint', $filter['apiEndpoint']);
        }

        if (!empty($filter['apiKey'])) {
            $qb->andWhere('vfl.apiKey = :apiKey')
                ->setParameter('apiKey', $filter['apiKey']);
        }

        return $qb->getQuery();
    }

    public function isBruteforce(string $ip): bool
    {
        $qb = $this->createQueryBuilder('vfl')
            ->select('CASE WHEN COUNT(vfl.id) > 10 THEN 1 ELSE 0 END AS count_exceeded')
            ->andWhere('vfl.ip = :ip')
            ->andWhere('vfl.time > :time')
            ->setParameter('ip', $ip)
            ->setParameter('time', CarbonImmutable::now()->subMinutes(5))
            ->getQuery();

        return (bool) $qb->getSingleScalarResult();
    }
}

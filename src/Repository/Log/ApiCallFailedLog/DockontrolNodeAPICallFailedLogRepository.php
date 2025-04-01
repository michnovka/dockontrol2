<?php

declare(strict_types=1);

namespace App\Repository\Log\ApiCallFailedLog;

use App\Entity\Log\ApiCallFailedLog\DockontrolNodeAPICallFailedLog;
use App\Extension\Type\DateRange;
use Carbon\CarbonImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DockontrolNodeAPICallFailedLog>
 *
 *  @psalm-type DockontrolNodeApiFailedCallFilterArray = array{
 *      time: ?DateRange,
 *      ip: ?string,
 *      dockontrolNodeApiKey: ?string,
 *      apiEndpoint: ?string
 *    }
 */
class DockontrolNodeAPICallFailedLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DockontrolNodeAPICallFailedLog::class);
    }

    /**
     * @psalm-param DockontrolNodeApiFailedCallFilterArray $filter
     */
    public function getQueryBuilder(array $filter): Query
    {
        $qb = $this->createQueryBuilder('dnafl');

        if (!empty($filter['time'])) {
            $qb->andWhere('dnafl.time BETWEEN :start AND :end')
                ->setParameter('start', $filter['time']->getStartDate()->format('Y-m-d H:i:s'))
                ->setParameter('end', $filter['time']->getEndDate()->format('Y-m-d H:i:s'));
        }

        if (!empty($filter['ip'])) {
            $qb->andWhere('dnafl.ip = :ip')
                ->setParameter('ip', $filter['ip']);
        }

        if (!empty($filter['dockontrolNodeApiKey'])) {
            $qb->andWhere('dnafl.dockontrolNodeAPIKey = :dockontrolNodeApiKey')
                ->setParameter('dockontrolNodeApiKey', $filter['dockontrolNodeApiKey']);
        }

        if (!empty($filter['apiEndpoint'])) {
            $qb->andWhere('dnafl.apiEndpoint = :apiEndpoint')
                ->setParameter('apiEndpoint', $filter['apiEndpoint']);
        }

        return $qb->getQuery();
    }

    public function isBruteforce(string $ip): bool
    {
        $qb = $this->createQueryBuilder('dnafl')
            ->select('CASE WHEN COUNT(dnafl.id) > 10 THEN 1 ELSE 0 END AS count_exceeded')
            ->andWhere('dnafl.ip = :ip')
            ->andWhere('dnafl.time > :time')
            ->setParameter('ip', $ip)
            ->setParameter('time', CarbonImmutable::now()->subMinutes(5))
            ->getQuery();

        return (bool) $qb->getSingleScalarResult();
    }
}

<?php

declare(strict_types=1);

namespace App\Repository\Log\ApiCallLog;

use App\Entity\Log\ApiCallLog\API2CallLog;
use App\Entity\User;
use App\Extension\Type\DateRange;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<API2CallLog>
 *
 *   @psalm-type Api2CallFilterArray = array{
 *      time: ?DateRange,
 *      user: ?User,
 *      ip: ?string,
 *      apiKey: ?string
 *   }
 */
class API2CallLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, API2CallLog::class);
    }

    /**
     * @psalm-param Api2CallFilterArray $filter
     */
    public function getQueryBuilder(array $filter): Query
    {
        $qb =  $this->createQueryBuilder('v');

        if (!empty($filter['time'])) {
            $qb->andWhere('v.time BETWEEN :start AND :end')
                ->setParameter('start', $filter['time']->getStartDate()->format('Y-m-d H:i:s'))
                ->setParameter('end', $filter['time']->getEndDate()->format('Y-m-d H:i:s'));
        }

        if (!empty($filter['user'])) {
            $qb
                ->innerJoin('v.user', 'u')
                ->andWhere('u = :user')
                ->setParameter('user', $filter['user']);
        }

        if (!empty($filter['ip'])) {
            $qb->andWhere('v.ip = :ip')
                ->setParameter('ip', $filter['ip']);
        }

        if (!empty($filter['apiKey'])) {
            $qb->andWhere('v.apiKey = :apiKey')
                ->setParameter('apiKey', $filter['apiKey']);
        }

        return $qb->getQuery();
    }
}

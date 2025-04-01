<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Action;
use App\Entity\ActionQueue;
use App\Entity\ActionQueueCronGroup;
use App\Entity\Enum\ActionQueueStatus;
use App\Entity\User;
use App\Extension\Type\DateRange;
use App\Form\Filter\ActionQueueFilterType;
use Carbon\CarbonImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ActionQueue>
 *
 * @psalm-type ActionQueueFilterType = array{
 *      timeStart: ?DateRange,
 *      user: ?User,
 *      action: ?Action,
 *      status: ?ActionQueueStatus,
 *  }
 */
class ActionQueueRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ActionQueue::class);
    }

    /**
     *@return array<array{count: int, timeStart: string, action: string}>
     */
    public function getStatistics(): array
    {
        return $this->createQueryBuilder('aq')
            ->innerJoin('aq.action', 'a')
            ->leftJoin('a.dockontrolNode', 'd')
            ->select('COUNT(aq.id) AS count')
            ->addSelect('DATE_FORMAT(aq.timeStart, \'%Y-%m\') AS timeStart')
            ->addSelect('a.friendlyName AS action')
            ->where('aq.countIntoStats = :countIntoStats')
            ->andWhere('aq.timeStart >= :timeStart')
            ->andWhere('d.enabled = :enabled')
            ->setParameter('enabled', true)
            ->setParameter('countIntoStats', true)
            ->setParameter('timeStart', CarbonImmutable::now()->startOfMonth()->subMonths(3))
            ->groupBy('timeStart')
            ->addGroupBy('action')
            ->orderBy('action', 'ASC')
            ->addOrderBy('timeStart', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @psalm-param ActionQueueFilterType $filter
     * @return array{items: ActionQueue[], currentPage: int, hasNextPage: bool}
     */
    public function getCombinedQueueQueryBuilder(
        array $filter,
        int $currentPage,
        int $recordsPerPage = 50,
    ): array {
        $qb = $this->createQueryBuilder('aq')
        ->innerJoin('aq.user', 'u')
        ->leftJoin('aq.guest', 'g')
        ->addSelect('u', 'g');


        if (!empty($filter['timeStart'])) {
            $qb->andWhere('aq.timeStart BETWEEN :start AND :end')
                ->setParameter('start', $filter['timeStart']->getStartDate()->format('Y-m-d H:i:s'))
                ->setParameter('end', $filter['timeStart']->getEndDate()->format('Y-m-d H:i:s'));
        }

        if (!empty($filter['user'])) {
            $qb->andWhere('u = :user')
                ->setParameter('user', $filter['user']);
        }

        if (!empty($filter['action'])) {
            $qb->andWhere('aq.action = :action')
                ->setParameter('action', $filter['action']);
        }

        if (!empty($filter['status'])) {
            $qb->andWhere('aq.status = :status')
                ->setParameter('status', $filter['status']);
        }

        $qb->orderBy('aq.timeStart', 'DESC')
            ->addOrderBy('aq.status', 'DESC');

        $qb->setFirstResult(($currentPage - 1) * $recordsPerPage);
        $qb->setMaxResults($recordsPerPage + 1);
        $queues = $qb->getQuery()->getResult();
        $hasNextPage = count($queues) > $recordsPerPage;

        if ($hasNextPage) {
            array_pop($queues);
        }

        return [
            'items' => $queues,
            'currentPage' => $currentPage,
            'hasNextPage' => $hasNextPage,
        ];
    }

    /**
     * @return null|ActionQueue[]
     */
    public function getPendingActionQueue(?ActionQueueCronGroup $cronGroup): ?array
    {
        $query =  $this->createQueryBuilder('aq')
            ->innerJoin('aq.action', 'a')
            ->andWhere('aq.executed = :executed')
            ->andWhere('aq.timeStart >= NOW()')
            ->setParameter('executed', false);

        if ($cronGroup instanceof ActionQueueCronGroup) {
            $query->innerJoin('a.cronGroup', 'cg')
                ->andWhere('a.cronGroup = :cronGroup')
                ->setParameter('cronGroup', $cronGroup);
        }

        return $query->orderBy('aq.timeStart', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<ActionQueue>
     */
    public function getLastFiveActionQueues(): array
    {
        return $this->createQueryBuilder('aq')
            ->orderBy('aq.timeCreated', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();
    }

    public function clearPendingQueueForCronGroup(ActionQueueCronGroup $actionQueueCronGroup): void
    {
        $this->createQueryBuilder('aq')
            ->delete()
            ->where('aq.status = :status')
            ->andWhere('aq.action IN (
                SELECT a.name FROM App\Entity\Action a WHERE a.actionQueueCronGroup = :actionQueueCronGroup
            )')
            ->setParameter('actionQueueCronGroup', $actionQueueCronGroup)
            ->setParameter('status', ActionQueueStatus::QUEUED)
            ->getQuery()
            ->execute();
    }

    /**
     * @return array<ActionQueue>
     */
    public function getActionQueuesForUser(User $user, ?int $limit = null): iterable
    {
        $qb = $this->createQueryBuilder('aq')
            ->where('aq.user = :user')
            ->setParameter('user', $user)
            ->orderBy('aq.timeCreated', 'DESC');

        if ($limit) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->toIterable();
    }

    public function cleanupActionQueue(int $clearActionQueueIntervalDays): void
    {
        $this->createQueryBuilder('aq')
            ->delete(ActionQueue::class, 'aq')
            ->where('aq.timeCreated < date_sub(now(), :interval, \'DAY\')')
            ->setParameter('interval', $clearActionQueueIntervalDays)
            ->getQuery()
            ->execute()
        ;
    }
}

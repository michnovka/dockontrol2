<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Action;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Action>
 */
class ActionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Action::class);
    }

    public function getQueryBuilder(
        ?string $cronGroupName = null,
        bool $prefetchActionBackupDockontrolNodes = false,
    ): Query {
        $qb = $this->createQueryBuilder('a')
            ->leftJoin('a.dockontrolNode', 'd')
            ->leftJoin('a.actionQueueCronGroup', 'cg');

        if ($prefetchActionBackupDockontrolNodes) {
            $qb->leftJoin('a.actionBackupDockontrolNodes', 'abdn')
                ->addSelect('d, abdn');
        }

        if (!empty($cronGroupName)) {
            $qb->andWhere('cg.name = :cronGroupName')
                ->setParameter('cronGroupName', $cronGroupName);
        }

        return $qb->getQuery();
    }

    /**
     * @return Action[]
     */
    public function getActionsExceptCarEnterExit(): array
    {
        return $this->createQueryBuilder('a')
            ->getQuery()
            ->getResult();
    }
}

<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Action;
use App\Entity\ActionBackupDockontrolNode;
use App\Entity\Enum\DockontrolNodeStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ActionBackupDockontrolNode>
 */
class ActionBackupDockontrolNodeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ActionBackupDockontrolNode::class);
    }

    public function findRandomBackupActionByParent(Action $parentAction): ?ActionBackupDockontrolNode
    {
        return $this->createQueryBuilder('abdn')
            ->innerJoin('abdn.dockontrolNode', 'd')
            ->where('abdn.parentAction = :parentAction')
            ->andWhere('d.status = :onlineStatus')
            ->setParameter('parentAction', $parentAction)
            ->setParameter('onlineStatus', DockontrolNodeStatus::ONLINE)
            ->addSelect('RAND() as HIDDEN rand')
            ->orderBy('rand')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}

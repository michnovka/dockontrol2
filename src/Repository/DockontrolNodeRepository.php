<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Button;
use App\Entity\Camera;
use App\Entity\DockontrolNode;
use App\Entity\Enum\DockontrolNodeStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DockontrolNode>
 */
class DockontrolNodeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DockontrolNode::class);
    }

    public function getQueryBuilder(): Query
    {
        return $this->createQueryBuilder('n')
            ->select('n as node')
            ->leftJoin('n.usersToNotifyWhenStatusChanges', 'u')
            ->leftJoin('n.building', 'b')
            ->addSelect('u, b')
            ->addSelect('COUNT(u) as totalNotifyUserWhenStatusChange')
            ->groupBy('n.id')
            ->getQuery()
            ;
    }

    /**
     * @return array{allNodesAreOnline: bool, totalNodes: int, onlineNodes: int}
     */
    public function getActiveNodesCount(): array
    {
        $qb = $this->createQueryBuilder('n')
            ->select(
                'CASE 
                    WHEN COUNT(n.id) = SUM(CASE WHEN n.status = :onlineStatus AND n.enabled = :enabled THEN 1 ELSE 0 END) 
                    THEN true 
                    ELSE false 
                 END as allNodesAreOnline'
            )
            ->addSelect(
                'COUNT(n.id) as totalNodes',
                'SUM(CASE WHEN n.status = :onlineStatus AND n.enabled = :enabled THEN 1 ELSE 0 END) as onlineNodes'
            )
            ->where('n.enabled = :enabled')
            ->setParameter('enabled', true)
            ->setParameter('onlineStatus', DockontrolNodeStatus::ONLINE);

        return $qb->getQuery()->getSingleResult();
    }

    /**
     * @return DockontrolNode[]
     */
    public function getNodesWhichAreNotOnline(): array
    {
        return $this->createQueryBuilder('n')
            ->where('n.status != :onlineStatus')
            ->andWhere('n.enabled = :enabled')
            ->setParameter('enabled', true)
            ->setParameter('onlineStatus', DockontrolNodeStatus::ONLINE)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @param Button[] $buttons
     */
    public function hasNodesWhichAreNotOnline(array $buttons): bool
    {
        return (bool) $this->createQueryBuilder('dn')
            ->select('1')
            ->join(Camera::class, 'c', 'WITH', 'c.dockontrolNode = dn')
            ->join(Button::class, 'b', 'WITH', '(b.camera1 = c OR b.camera2 = c OR b.camera3 = c OR b.camera4 = c)')
            ->where('b IN (:buttons)')
            ->andWhere('dn.status != :onlineStatus')
            ->andWhere('dn.enabled = :enabled')
            ->setParameter('buttons', $buttons)
            ->setParameter('onlineStatus', DockontrolNodeStatus::ONLINE)
            ->setParameter('enabled', true)
            ->setMaxResults(1)
            ->getQuery()->getOneOrNullResult();
    }

    /**
     * @return DockontrolNode[]
     */
    public function getAllEnabledNodes(): array
    {
        return $this->createQueryBuilder('dn')
            ->where('dn.enabled = :enabled')
            ->setParameter('enabled', true)
            ->getQuery()
            ->getResult();
    }
}

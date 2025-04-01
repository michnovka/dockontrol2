<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Camera;
use App\Entity\CameraBackup;
use App\Entity\Enum\DockontrolNodeStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CameraBackup>
 */
class CameraBackupRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CameraBackup::class);
    }

    public function findRandomBackupByParentCamera(Camera $parentCamera): ?CameraBackup
    {
        return $this->createQueryBuilder('cb')
            ->innerJoin('cb.dockontrolNode', 'dn')
            ->where('cb.parentCamera = :parentCamera')
            ->andWhere('dn.status = :onlineStatus')
            ->setParameter('parentCamera', $parentCamera)
            ->setParameter('onlineStatus', DockontrolNodeStatus::ONLINE)
            ->addSelect('RAND() as HIDDEN rand')
            ->orderBy('rand')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}

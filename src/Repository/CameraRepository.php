<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Camera;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Camera>
 */
class CameraRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Camera::class);
    }

    public function getQueryBuilder(): Query
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.cameraBackups', 'cb')
            ->leftJoin('c.dockontrolNode', 'd')
            ->leftJoin('c.permissionRequired', 'p')
            ->select('c as camera')
            ->addSelect('count(cb) as cameraBackups', 'd', 'p')
            ->groupBy('c.nameId')
            ->getQuery();
    }
}

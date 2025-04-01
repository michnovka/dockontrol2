<?php

declare(strict_types=1);

namespace App\Helper;

use App\Entity\CameraBackup;
use Doctrine\ORM\EntityManagerInterface;

readonly class CameraBackupHelper
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function saveCameraBackup(CameraBackup $cameraBackup): void
    {
        $this->entityManager->persist($cameraBackup);
        $this->entityManager->flush();
    }

    public function removeCameraBackup(CameraBackup $cameraBackup): void
    {
        $this->entityManager->remove($cameraBackup);
        $this->entityManager->flush();
    }
}

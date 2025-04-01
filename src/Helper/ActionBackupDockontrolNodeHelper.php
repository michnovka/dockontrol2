<?php

declare(strict_types=1);

namespace App\Helper;

use App\Entity\ActionBackupDockontrolNode;
use Doctrine\ORM\EntityManagerInterface;

readonly class ActionBackupDockontrolNodeHelper
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function saveBackupAction(ActionBackupDockontrolNode $actionBackupDockontrolNode): void
    {
        $this->entityManager->persist($actionBackupDockontrolNode);
        $this->entityManager->flush();
    }

    public function removeBackupAction(ActionBackupDockontrolNode $actionBackupDockontrolNode): void
    {
        $this->entityManager->remove($actionBackupDockontrolNode);
        $this->entityManager->flush();
    }
}

<?php

declare(strict_types=1);

namespace App\Helper;

use App\Entity\Permission;
use Doctrine\ORM\EntityManagerInterface;

readonly class PermissionHelper
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function savePermission(Permission $permission): void
    {
        $this->entityManager->persist($permission);
        $this->entityManager->flush();
    }

    public function deletePermission(Permission $permission): void
    {
        $this->entityManager->remove($permission);
        $this->entityManager->flush();
    }
}

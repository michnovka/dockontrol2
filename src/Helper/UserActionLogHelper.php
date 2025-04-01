<?php

declare(strict_types=1);

namespace App\Helper;

use App\Entity\Log\UserActionLog;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

readonly class UserActionLogHelper
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function addUserActionLog(string $description, ?User $user = null, bool $flush = true): void
    {
        $adminActionLog = new UserActionLog($user, $description);
        $this->entityManager->persist($adminActionLog);
        if ($flush) {
            $this->entityManager->flush();
        }
    }
}

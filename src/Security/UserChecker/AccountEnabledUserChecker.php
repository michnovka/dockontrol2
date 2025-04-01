<?php

declare(strict_types=1);

namespace App\Security\UserChecker;

use App\Entity\User as AppUser;
use Override;
use Symfony\Component\Security\Core\Exception\DisabledException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

readonly class AccountEnabledUserChecker implements UserCheckerInterface
{
    #[Override]
    public function checkPreAuth(UserInterface $user): void
    {

        if (!$user instanceof AppUser) {
            return;
        }

        if (!$user->isEnabled()) {
            throw new DisabledException();
        }
    }

    #[Override]
    public function checkPostAuth(UserInterface $user): void
    {
        return;
    }
}

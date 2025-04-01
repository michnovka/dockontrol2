<?php

declare(strict_types=1);

namespace App\Security\UserChecker;

use App\Entity\Guest;
use Override;
use Symfony\Component\Security\Core\Exception\AccountExpiredException;
use Symfony\Component\Security\Core\Exception\DisabledException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

readonly class GuestHashUserChecker implements UserCheckerInterface
{
    public function __construct(
        private AccountEnabledUserChecker $accountEnabledUserChecker,
        private TranslatorInterface $translator,
    ) {
    }

    #[Override]
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof Guest) {
            return;
        }

        if (!$user->isEnabled()) {
            throw new DisabledException($this->translator->trans('dockontrol.error.guest_401.account_disabled'));
        }

        if ($user->isGuestPassValid()) {
            throw new AccountExpiredException($this->translator->trans('dockontrol.error.guest_401.token_expired'));
        }

        $this->accountEnabledUserChecker->checkPreAuth($user->getUser());
    }

    #[Override]
    public function checkPostAuth(UserInterface $user): void
    {
        if (!$user instanceof Guest) {
            return;
        }

        $this->accountEnabledUserChecker->checkPostAuth($user->getUser());
    }
}

<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\Enum\UserRole;
use App\Entity\User;
use Override;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @template TSubject
 * @extends Voter<string, TSubject>
 */
class SwitchUserVoter extends Voter
{
    #[Override]
    public function supportsAttribute(string $attribute): bool
    {
        return $attribute === 'CAN_SWITCH_USER';
    }

    #[Override]
    public function supportsType(string $subjectType): bool
    {
        return $subjectType === User::class;
    }

    #[Override]
    public function supports(string $attribute, mixed $subject): bool
    {
        return $this->supportsAttribute($attribute) && $this->supportsType(get_debug_type($subject));
    }

    /**
     * @inheritDoc
     */
    #[Override]
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        if ($user->getRole() === UserRole::SUPER_ADMIN && $subject->isEnabled()) {
            return true;
        }

        return false;
    }
}

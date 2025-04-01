<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\Enum\UserRole;
use App\Entity\Guest;
use Override;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * @extends AbstractVoter<Guest>
 */
class GuestVoter extends AbstractVoter
{
    public const string CREATE = 'ACTION_CREATE';
    public const string DELETE = 'ACTION_DELETE';
    public const string EDIT = 'ACTION_EDIT';

    #[Override]
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $this->setUserFromTokenInterface($token);

        return match ($attribute) {
            self::DELETE, self::CREATE, self::EDIT => $this->isAdminAndManagesUser($subject->getUser()) ||
                ($this->isUserRoleGranted(UserRole::TENANT) && $this->isThisUser($subject->getUser())),
            default => false,
        };
    }

    /**
     * @inheritDoc
     */
    #[Override]
    protected function getSupportedEntity(): string
    {
        return Guest::class;
    }

    /**
     * @inheritDoc
     */
    #[Override]
    protected function getSupportedAttributes(): array
    {
        return [self::CREATE, self::DELETE, self::EDIT];
    }
}

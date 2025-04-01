<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\Enum\UserRole;
use App\Entity\Nuki;
use Override;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * @extends AbstractVoter<Nuki>
 */
class NukiVoter extends AbstractVoter
{
    public const string CREATE = 'ACTION_CREATE';
    public const string DELETE = 'ACTION_DELETE';
    public const string EDIT = 'ACTION_EDIT';
    public const string MANAGE_PIN = 'ACTION_MANAGE_PIN';
    public const string ENGAGE = 'ACTION_ENGAGE';

    #[Override]
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $this->setUserFromTokenInterface($token);

        return match ($attribute) {
            self::CREATE, self::EDIT, self::DELETE => $this->isUserRoleGranted(UserRole::SUPER_ADMIN) ||
                $this->isAdminAndManagesUser($subject->getUser()) || ($this->isUserRoleGranted(UserRole::TENANT) && $this->isThisUser($subject->getUser())),
            self::MANAGE_PIN => $this->isUserRoleGranted(UserRole::SUPER_ADMIN) || ($this->isUserRoleGranted(UserRole::TENANT) && $this->isThisUser($subject->getUser())),
            self::ENGAGE => $this->isUserRoleGranted(UserRole::TENANT) && $this->isThisUser($subject->getUser()),
            default => false,
        };
    }

    /**
     * @inheritDoc
     */
    #[Override]
    protected function getSupportedEntity(): string
    {
        return Nuki::class;
    }

    /**
     * @inheritDoc
     */
    #[Override]
    protected function getSupportedAttributes(): array
    {
        return [self::CREATE, self::DELETE, self::EDIT, self::MANAGE_PIN, self::ENGAGE];
    }
}

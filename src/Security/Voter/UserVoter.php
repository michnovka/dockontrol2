<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\Enum\UserRole;
use App\Entity\User;
use Override;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * @extends AbstractVoter<User>
 */
class UserVoter extends AbstractVoter
{
    public const string CREATE = 'ACTION_CREATE';
    public const string DELETE = 'ACTION_DELETE';
    public const string EDIT = 'ACTION_EDIT';
    public const string MANAGE = 'ACTION_MANAGE';
    public const string VIEW = 'ACTION_VIEW';
    public const string MARK_EMAIL_VERIFIED = 'ACTION_MARK_EMAIL_VERIFIED';

    public const string MARK_PHONE_VERIFIED = 'ACTION_MARK_PHONE_VERIFIED';

    public const string MANAGE_USER_GROUP = 'ACTION_MANAGE_USER_GROUP';

    #[Override]
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $this->setUserFromTokenInterface($token);

        return match ($attribute) {
            self::CREATE, self::VIEW, self::MANAGE => $this->isUserRoleGranted(UserRole::SUPER_ADMIN)
                || (!$subject->isAdmin() && $this->isAdminAndManagesUser($subject)) || $this->isLandlordAndManagesTenant($subject),
            self::EDIT => $this->isThisUser($subject),
            self::DELETE => !$this->isThisUser($subject) && ($this->isUserRoleGranted(UserRole::SUPER_ADMIN) || $this->isLandlordAndManagesTenant($subject)),
            self::MARK_EMAIL_VERIFIED, self::MARK_PHONE_VERIFIED, self::MANAGE_USER_GROUP => $this->isUserRoleGranted(UserRole::SUPER_ADMIN),
            default => false,
        };
    }

    /**
     * @inheritDoc
     */
    #[Override]
    protected function getSupportedEntity(): string
    {
        return User::class;
    }

    /**
     * @inheritDoc
     */
    #[Override]
    protected function getSupportedAttributes(): array
    {
        return [self::CREATE, self::DELETE, self::EDIT, self::VIEW, self::MANAGE, self::MARK_EMAIL_VERIFIED, self::MARK_PHONE_VERIFIED, self::MANAGE_USER_GROUP];
    }
}

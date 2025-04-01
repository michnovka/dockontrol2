<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\Button;
use App\Entity\Enum\UserRole;
use App\Entity\Permission;
use Override;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * @extends AbstractVoter<Button>
 */
class ButtonVoter extends AbstractVoter
{
    public const string CREATE = 'ACTION_CREATE';
    public const string DELETE = 'ACTION_DELETE';
    public const string EDIT = 'ACTION_EDIT';
    public const string EXECUTE = 'ACTION_EXECUTE';

    #[Override]
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $this->setUserFromTokenInterface($token);
        $buttonPermission = $subject->getPermission();

        return match ($attribute) {
            self::CREATE, self::EDIT, self::DELETE => $this->isUserRoleGranted(UserRole::SUPER_ADMIN),
            self::EXECUTE => ($this->isUserRoleGranted(UserRole::TENANT) || $this->isGuest()) && $buttonPermission instanceof Permission && $this->hasUserPermission($buttonPermission, false),
            default => false,
        };
    }

    /**
     * @inheritDoc
     */
    #[Override]
    protected function getSupportedEntity(): string
    {
        return Button::class;
    }

    /**
     * @inheritDoc
     */
    #[Override]
    protected function getSupportedAttributes(): array
    {
        return [self::CREATE, self::DELETE, self::EDIT, self::EXECUTE];
    }
}

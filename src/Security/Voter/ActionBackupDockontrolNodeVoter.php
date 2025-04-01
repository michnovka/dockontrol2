<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\ActionBackupDockontrolNode;
use App\Entity\Enum\UserRole;
use Override;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * @extends AbstractVoter<ActionBackupDockontrolNode>
 */
class ActionBackupDockontrolNodeVoter extends AbstractVoter
{
    public const string CREATE = 'ACTION_CREATE';
    public const string DELETE = 'ACTION_DELETE';
    public const string EDIT = 'ACTION_EDIT';
    public const string EXECUTE = 'ACTION_EXECUTE';

    #[Override]
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $this->setUserFromTokenInterface($token);

        return match ($attribute) {
            self::CREATE, self::DELETE, self::EDIT => $this->isUserRoleGranted(UserRole::SUPER_ADMIN),
            default => false,
        };
    }

    /**
     * @inheritDoc
     */
    #[Override]
    protected function getSupportedEntity(): string
    {
        return ActionBackupDockontrolNode::class;
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

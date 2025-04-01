<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\Camera;
use App\Entity\Enum\UserRole;
use App\Entity\Permission;
use Override;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * @extends AbstractVoter<Camera>
 */
class CameraVoter extends AbstractVoter
{
    public const string CREATE = 'ACTION_CREATE';
    public const string DELETE = 'ACTION_DELETE';
    public const string EDIT = 'ACTION_EDIT';
    public const string SHOW = 'ACTION_SHOW';

    #[Override]
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        /** @var Camera $subject */
        $this->setUserFromTokenInterface($token);
        switch ($attribute) {
            case self::CREATE:
            case self::EDIT:
            case self::DELETE:
                return $this->isUserRoleGranted(UserRole::SUPER_ADMIN);
            case self::SHOW:
                if ($this->isUserRoleGranted(UserRole::SUPER_ADMIN)) {
                    return true;
                }

                if (!$this->isGuest() && !$this->isUserRoleGranted(UserRole::TENANT)) {
                    return false;
                }

                if (!$this->hasCameraAccess()) {
                    return false;
                }

                $permission = $subject->getPermissionRequired();

                if (!$permission instanceof Permission) {
                    return true;
                }

                return $this->hasUserPermission($permission, false);
            default:
                return false;
        }
    }

    /**
     * @inheritDoc
     */
    #[Override]
    protected function getSupportedEntity(): string
    {
        return Camera::class;
    }

    /**
     * @inheritDoc
     */
    #[Override]
    protected function getSupportedAttributes(): array
    {
        return [self::CREATE, self::DELETE, self::EDIT, self::SHOW];
    }
}

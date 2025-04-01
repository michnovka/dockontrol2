<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\Announcement;
use App\Entity\Building;
use App\Entity\Enum\UserRole;
use Override;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * @extends AbstractVoter<Announcement>
 */
class AnnouncementVoter extends AbstractVoter
{
    public const string CREATE = 'ACTION_CREATE';
    public const string DELETE = 'ACTION_DELETE';
    public const string EDIT = 'ACTION_EDIT';

    #[Override]
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $this->setUserFromTokenInterface($token);
        $announcementBuilding = $subject->getBuilding();
        return match ($attribute) {
            self::CREATE, self::EDIT, self::DELETE => $this->isUserRoleGranted(UserRole::SUPER_ADMIN) || $announcementBuilding instanceof Building && $this->isAdminAndManagesBuilding($announcementBuilding),
            default => false,
        };
    }

    /**
     * @inheritDoc
     */
    #[Override]
    protected function getSupportedEntity(): string
    {
        return Announcement::class;
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

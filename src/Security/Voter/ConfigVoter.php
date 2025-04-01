<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\Config;
use App\Entity\Enum\UserRole;
use Override;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * @extends AbstractVoter<Config>
 */
class ConfigVoter extends AbstractVoter
{
    public const string DELETE = 'ACTION_DELETE';
    public const string EDIT = 'ACTION_EDIT';

    #[Override]
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $this->setUserFromTokenInterface($token);

        return match ($attribute) {
            self::EDIT, self::DELETE => $this->isUserRoleGranted(UserRole::SUPER_ADMIN),
            default => false,
        };
    }

    /**
     * @inheritDoc
     */
    #[Override]
    protected function getSupportedEntity(): string
    {
        return Config::class;
    }

    /**
     * @inheritDoc
     */
    #[Override]
    protected function getSupportedAttributes(): array
    {
        return [self::DELETE, self::EDIT];
    }
}

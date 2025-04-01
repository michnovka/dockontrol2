<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\SignupCode;
use Override;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * @extends AbstractVoter<SignupCode>
 */
class SignupCodeVoter extends AbstractVoter
{
    public const string CREATE = 'ACTION_CREATE';
    public const string DELETE = 'ACTION_DELETE';
    public const string EDIT = 'ACTION_EDIT';

    #[Override]
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $this->setUserFromTokenInterface($token);

        // we allow deletion of SignupCode only for Admin that has EDIT access to a building
        return match ($attribute) {
            self::CREATE, self::EDIT, self::DELETE => $this->isAdminAndManagesBuilding($subject->getApartment()->getBuilding()),
            default => false,
        };
    }

    /**
     * @inheritDoc
     */
    #[Override]
    protected function getSupportedEntity(): string
    {
        return SignupCode::class;
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

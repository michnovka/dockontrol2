<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\Guest;
use App\Entity\User;
use Override;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @template TSubject
 * @extends Voter<string, TSubject>
 */
class UserCapabilityVoter extends Voter
{
    public const string PERMISSION_CAMERA_ACCESS = 'PERMISSION_CAMERA_ACCESS';
    public const string PERMISSION_CAR_ENTER_EXIT = 'PERMISSION_CAR_ENTER_EXIT';

    public function __construct(private readonly Security $security)
    {
    }

    #[Override]
    public function supportsAttribute(string $attribute): bool
    {
        return str_starts_with($attribute, 'PERMISSION_');
    }

    #[Override]
    public function supportsType(string $subjectType): bool
    {
        return $subjectType === 'null';
    }

    #[Override]
    protected function supports(string $attribute, mixed $subject): bool
    {
        return null === $subject && $this->supportsAttribute($attribute);
    }

    #[Override]
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {

        if (!$this->security->isGranted('ROLE_TENANT') && !$this->security->isGranted('ROLE_GUEST')) {
            return false;
        }

        /** @var User|Guest $userInterfaceFromToken */
        $userInterfaceFromToken = $token->getUser();

        $user = $userInterfaceFromToken;
        if ($user instanceof Guest) {
            $user = $user->getUser();
        }

        return match ($attribute) {
            self::PERMISSION_CAMERA_ACCESS => $this->security->isGranted('ROLE_TENANT') && $user->getHasCameraAccess(),
            self::PERMISSION_CAR_ENTER_EXIT => $user->isCarEnterExitAllowed(),
            default => false,
        };
    }
}

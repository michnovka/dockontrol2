<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\Building;
use App\Entity\Enum\UserRole;
use App\Entity\Guest;
use App\Entity\Permission;
use App\Entity\User;
use App\Helper\UserHelper;
use App\Repository\UserRepository;
use Override;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @template TSubject
 * @extends Voter<string, TSubject>
 */
abstract class AbstractVoter extends Voter
{
    private ?UserInterface $user;

    public function __construct(
        private readonly Security $security,
        private readonly UserRepository $userRepository,
        private readonly UserHelper $userHelper,
    ) {
    }

    #[Override]
    public function supportsType(string $subjectType): bool
    {
        return is_a($subjectType, static::getSupportedEntity(), true);
    }

    #[Override]
    public function supportsAttribute(string $attribute): bool
    {
        return in_array($attribute, static::getSupportedAttributes(), true);
    }

    /** @return class-string<TSubject> */
    abstract protected function getSupportedEntity(): string;

    /** @return array<string> array of attributes that are supported by this voter*/
    abstract protected function getSupportedAttributes(): array;

    /**
     * @inheritDoc
     */
    #[Override]
    protected function supports(string $attribute, mixed $subject): bool
    {
        return $this->supportsType(get_debug_type($subject)) && $this->supportsAttribute($attribute);
    }

    protected function setUserFromTokenInterface(TokenInterface $tokenInterface): void
    {
        $this->user = $tokenInterface->getUser();
    }

    protected function isGuest(): bool
    {
        return $this->user instanceof Guest;
    }

    /** @param array<UserRole>|UserRole $roles */
    protected function isUserRoleGranted(array|UserRole $roles): bool
    {
        if (!$this->user instanceof User) {
            return false;
        }

        if ($roles instanceof UserRole) {
            $roles = [$roles];
        }

        return array_any($roles, fn ($role) => $this->security->isGranted($role->value));
    }

    protected function isThisUser(User $user): bool
    {
        if (!$this->user instanceof User) {
            return false;
        }

        return $this->user->getId() === $user->getId();
    }

    protected function isAdminAndManagesUser(User $user): bool
    {
        if (!$this->user instanceof User) {
            return false;
        }

        $apartment = $user->getApartment();

        if ($apartment === null) {
            return $this->isUserRoleGranted(UserRole::SUPER_ADMIN);
        }

        $building = $apartment->getBuilding();

        return $this->isAdminAndManagesBuilding($building);
    }

    protected function isAdminAndManagesBuilding(Building $building): bool
    {
        if (!$this->user instanceof User) {
            return false;
        }

        // SUPER_ADMIN manages all buildings
        if ($this->isUserRoleGranted(UserRole::SUPER_ADMIN)) {
            return true;
        }

        // This makes sure we have at least ADMIN if not SUPER_ADMIN
        if (!$this->isUserRoleGranted(UserRole::ADMIN)) {
            return false;
        }

        return $this->userRepository->doesAdminManageBuilding($this->user, $building);
    }

    protected function isLandlordAndManagesTenant(User $user): bool
    {
        if (!$this->isUserRoleGranted([UserRole::LANDLORD])) {
            return false;
        }

        return $user->getLandlord() === $this->user;
    }

    protected function isGranted(string $attribute, mixed $subject): bool
    {
        return $this->security->isGranted($attribute, $subject);
    }

    protected function hasCameraAccess(): bool
    {
        if (!$this->user instanceof User) {
            return false;
        }

        return $this->user->getHasCameraAccess();
    }

    protected function hasUserPermission(Permission $permission, bool $noGuest = true): bool
    {
        if (!($this->user instanceof User || (!$noGuest && $this->user instanceof Guest))) {
            return false;
        }

        $permissions = $this->userHelper->getCachedPermissions($this->user instanceof Guest ? $this->user->getUser() : $this->user, true);

        return $permissions->contains($permission->getName());
    }
}

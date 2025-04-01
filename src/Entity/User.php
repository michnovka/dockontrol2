<?php

declare(strict_types=1);

namespace App\Entity;

use App\Controller\CP\AccessManagement\UserController;
use App\Doctrine\ORM\Attribute\SyncWithLandlord;
use App\DTO\CameraSessionData;
use App\Entity\Enum\ButtonPressType;
use App\Entity\Enum\UserRole;
use App\Repository\UserRepository;
use App\Security\User\ApiKeyPairAuthenticatedUserInterface;
use Carbon\CarbonImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Override;
use ReflectionProperty;
use RuntimeException;
use SensitiveParameter;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ORM\Table(name: '`users`')]
#[ORM\UniqueConstraint('users_email_index', columns: ['email'])]
class User implements ApiKeyPairAuthenticatedUserInterface, PasswordAuthenticatedUserInterface, SearchableEntityInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'created_by', nullable: true, onDelete: 'SET NULL', options: ['default' => 1])]
    private ?User $createdBy;

    #[ORM\Column(length: 255)]
    private string $name;

    /**
     * @var non-empty-string $email
     */
    #[ORM\Column(length: 255, unique: true)]
    #[Assert\Email]
    private string $email;

    #[ORM\Column(length: 32)]
    #[Assert\Regex(
        pattern: '/^\d+$/',
    )]
    private string $phone;

    #[ORM\Column(length: 255)]
    private string $password;

    #[ORM\ManyToOne(targetEntity: Apartment::class, fetch: 'LAZY', inversedBy: 'users')]
    #[SyncWithLandlord]
    private ?Apartment $apartment = null;

    #[ORM\Column(options: ['default' => ButtonPressType::HOLD])]
    private ButtonPressType $buttonPressType = ButtonPressType::HOLD;

    #[ORM\Column(options: ['default' => true])]
    #[SyncWithLandlord]
    private bool $enabled = true;

    #[ORM\Column(options: ['default' => true])]
    #[SyncWithLandlord]
    private bool $hasCameraAccess = true;

    #[ORM\Column(options: ['default' => true])]
    #[SyncWithLandlord]
    private bool $canCreateGuests = true;

    #[ORM\Column(nullable: true)]
    private ?CarbonImmutable $lastLoginTime = null;

    #[ORM\Column]
    private CarbonImmutable $createdTime;

    #[ORM\Column(type: 'uuid', nullable: true)]
    private ?Uuid $resetPasswordToken = null;

    #[ORM\Column(nullable: true)]
    private ?CarbonImmutable $resetPasswordTokenTimeCreated;

    #[ORM\Column(nullable: true)]
    private ?CarbonImmutable $resetPasswordTokenTimeExpires;

    #[ORM\Column(nullable: false, options: ['default' => UserRole::LANDLORD])]
    private UserRole $role = UserRole::LANDLORD;

    /**
     * @var Collection<int, Group>
     */
    #[ORM\ManyToMany(targetEntity: Group::class, inversedBy: 'users', fetch: 'LAZY')]
    #[ORM\JoinTable(
        name: 'user_group',
        joinColumns: [new ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', onDelete: 'CASCADE')],
        inverseJoinColumns: [new ORM\JoinColumn(name: 'group_id', referencedColumnName: 'id')]
    )]
    #[ORM\OrderBy(['name' => 'ASC'])]
    #[SyncWithLandlord]
    private Collection $groups;

    #[ORM\OneToMany(targetEntity: ActionQueue::class, mappedBy: 'user')]
    private Collection $actionQueues;

    /**
     * @var Collection<int, Building>
     */
    #[ORM\ManyToMany(targetEntity: Building::class)]
    #[ORM\JoinTable(
        name: 'user_admin_buildings',
        joinColumns: [new ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', onDelete: 'CASCADE')],
        inverseJoinColumns: [new ORM\JoinColumn(name: 'building_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    )]
    #[ORM\OrderBy(['name' => 'ASC'])]
    private Collection $adminBuildings;

    #[ORM\OneToMany(targetEntity: Nuki::class, mappedBy: 'user')]
    private Collection $nukis;

    #[ORM\Column(type: 'boolean', nullable: false, options: ['default' => false])]
    private bool $customCarEnterDetails = false;

    #[ORM\Column(nullable: false)]
    private CarbonImmutable $passwordSetTime;

    #[ORM\Column(nullable: true)]
    private ?CarbonImmutable $timeLastAction = null;

    #[ORM\OneToMany(targetEntity: APIKey::class, mappedBy: 'user', fetch: 'LAZY')]
    private Collection $apiKeys;

    private ?APIKey $currentApiKey = null;

    #[ORM\Column(type: 'boolean', nullable: false, options: ['default' => false])]
    private bool $customSorting = false;

    /**
     * @var Collection<int, CustomSortingGroup>
     */
    #[ORM\OneToMany(targetEntity: CustomSortingGroup::class, mappedBy: 'user')]
    #[ORM\OrderBy(['sortIndex' => 'ASC'])]
    private Collection $customSortingGroups;

    private ?CameraSessionData $cameraSessionData = null;

    #[ORM\Column(type: 'boolean', nullable: false, options: ['default' => true])]
    #[SyncWithLandlord]
    private bool $carEnterExitAllowed = true;

    #[ORM\Column(type: 'boolean', nullable: false, options: ['default' => true])]
    private bool $carEnterExitShow = true;

    #[ORM\Column(type: 'boolean', nullable: false, options: ['default' => false])]
    private bool $emailVerified = false;

    #[ORM\Column(nullable: true)]
    private ?CarbonImmutable $emailVerifiedTime = null;

    #[ORM\Column(nullable: true)]
    private ?CarbonImmutable $lastEmailSentTime = null;

    #[ORM\Column(nullable: false, options: ['default' => true])]
    private bool $disableAutomaticallyDueToInactivity = true;

    #[ORM\Column(nullable: false, options: ['default' => false])]
    private bool $phoneVerified = false;

    #[ORM\Column(nullable: false)]
    #[Assert\Range(min: 1, max: 9999)]
    private int $phoneCountryPrefix;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'tenants')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?self $landlord = null;

    /**
     * @var Collection<int, self>
     */
    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'landlord')]
    private Collection $tenants;

    #[ORM\Column(nullable: true)]
    private ?CarbonImmutable $timeTosAccepted = null;

    public function __construct()
    {
        $this->createdTime = $this->passwordSetTime = new CarbonImmutable();
        $this->groups = new ArrayCollection();
        $this->actionQueues = new ArrayCollection();
        $this->adminBuildings = new ArrayCollection();
        $this->nukis = new ArrayCollection();
        $this->apiKeys = new ArrayCollection();
        $this->customSortingGroups = new ArrayCollection();
        $this->tenants = new ArrayCollection();
    }

    #[Assert\Callback]
    public function validateTenantAndTenantApartment(ExecutionContextInterface $context): void
    {
        $landlord = $this->getLandlord();
        if ($this->getRole() === UserRole::TENANT && $landlord === null) {
            $context
                ->buildViolation('A tenant must have a landlord.')
                ->atPath('landlord')
                ->addViolation();
        }

        if ($this->getRole() === UserRole::TENANT && $landlord !== null && empty($landlord->getApartment())) {
            $context
                ->buildViolation('A landlord must have the apartment.')
                ->atPath('apartment')
                ->addViolation();
        }

        if ($this->getRole() === UserRole::TENANT && $landlord !== null && $this->getApartment() !== $landlord->getApartment()) {
            $context
                ->buildViolation('A tenant must have the same apartment as its landlord.')
                ->atPath('apartment')
                ->addViolation();
        }
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): self
    {
        $this->createdBy = $createdBy;

        return $this;
    }


    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @param non-empty-string $email
     */
    public function setEmail(string $email): self
    {
        if (!empty($this->email) && $this->email !== $email) {
            $this->emailVerified = false;
            $this->emailVerifiedTime = null;
        }
        $this->email = $email;

        return $this;
    }

    public function getPhone(): string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): self
    {
        if (!empty($this->phone) && $this->phone !== $phone) {
            $this->phoneVerified = false;
        }

        $this->phone = $phone;

        return $this;
    }

    #[Override]
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(#[SensitiveParameter] string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function getApartment(): ?Apartment
    {
        return $this->apartment;
    }

    public function setApartment(?Apartment $apartment): self
    {
        $this->apartment = $apartment;

        return $this;
    }

    public function getButtonPressType(): ButtonPressType
    {
        return $this->buttonPressType;
    }

    public function setButtonPressType(ButtonPressType $buttonPressType): self
    {
        $this->buttonPressType = $buttonPressType;

        return $this;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;

        return $this;
    }

    public function getHasCameraAccess(): bool
    {
        return $this->hasCameraAccess;
    }

    public function setHasCameraAccess(bool $hasCameraAccess): self
    {
        $this->hasCameraAccess = $hasCameraAccess;

        return $this;
    }

    public function isCanCreateGuests(): bool
    {
        return $this->canCreateGuests;
    }

    public function setCanCreateGuests(bool $canCreateGuests): self
    {
        $this->canCreateGuests = $canCreateGuests;

        return $this;
    }

    public function getLastLoginTime(): ?CarbonImmutable
    {
        return $this->lastLoginTime;
    }

    public function setLastLoginTime(?CarbonImmutable $lastLoginTime): self
    {
        $this->lastLoginTime = $lastLoginTime;

        return $this;
    }

    public function getCreatedTime(): CarbonImmutable
    {
        return $this->createdTime;
    }

    public function setCreatedTime(CarbonImmutable $createdTime): self
    {
        $this->createdTime = $createdTime;

        return $this;
    }

    /**
     * @return Collection<int, Group>
     */
    public function getGroups(): Collection
    {
        return $this->groups;
    }

    public function addGroup(Group $userGroup): static
    {
        if (!$this->groups->contains($userGroup)) {
            $this->groups->add($userGroup);
        }

        return $this;
    }

    public function removeGroup(Group $userGroup): static
    {
        $this->groups->removeElement($userGroup);

        return $this;
    }

    /**
     * @return string[]
     */
    #[Override]
    public function getRoles(): array
    {
        return [$this->getRole()->value];
    }

    #[Override]
    public function eraseCredentials(): void
    {
        // TODO: Implement eraseCredentials() method.
    }

    #[Override]
    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function getActionQueues(): Collection
    {
        return $this->actionQueues;
    }

    public function getRole(): UserRole
    {
        return $this->role;
    }

    public function setRole(UserRole $role): self
    {
        $this->role = $role;

        return $this;
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, [UserRole::ADMIN, UserRole::SUPER_ADMIN]);
    }

    /**
     * @return Collection<int, Building>
     */
    public function getAdminBuildings(): Collection
    {
        return $this->adminBuildings;
    }

    public function addAdminBuilding(Building $adminBuilding): static
    {
        if (!$this->adminBuildings->contains($adminBuilding)) {
            $this->adminBuildings->add($adminBuilding);
        }

        return $this;
    }

    public function removeAdminBuilding(Building $adminBuilding): static
    {
        $this->adminBuildings->removeElement($adminBuilding);

        return $this;
    }

    /**
     * @return Collection<int, Nuki>
     */
    public function getNukis(): Collection
    {
        return $this->nukis;
    }

    #[Override]
    public function getTwigDisplayValue(): string
    {
        return $this->name . ' (' . $this->apartment?->getName() . ') ';
    }

    /**
     * @inheritdoc
     */
    #[Override]
    public static function getSearchAPIController(): string
    {
        return UserController::class;
    }

    public function isCustomCarEnterDetails(): bool
    {
        return $this->customCarEnterDetails;
    }

    public function setCustomCarEnterDetails(bool $customCarEnterDetails): self
    {
        $this->customCarEnterDetails = $customCarEnterDetails;

        return $this;
    }

    public function getPasswordSetTime(): CarbonImmutable
    {
        return $this->passwordSetTime;
    }

    public function setPasswordSetTime(CarbonImmutable $passwordSetTime): self
    {
        $this->passwordSetTime = $passwordSetTime;

        return $this;
    }

    public function getTimeLastAction(): ?CarbonImmutable
    {
        return $this->timeLastAction;
    }

    public function setTimeLastAction(?CarbonImmutable $timeLastAction): static
    {
        $this->timeLastAction = $timeLastAction;

        return $this;
    }

    public function getResetPasswordToken(): ?Uuid
    {
        return $this->resetPasswordToken;
    }

    public function setResetPasswordToken(?Uuid $resetPasswordToken): void
    {
        $this->resetPasswordToken = $resetPasswordToken;
    }

    public function getResetPasswordTokenTimeCreated(): ?CarbonImmutable
    {
        return $this->resetPasswordTokenTimeCreated;
    }

    public function setResetPasswordTokenTimeCreated(?CarbonImmutable $resetPasswordTokenTimeCreated): void
    {
        $this->resetPasswordTokenTimeCreated = $resetPasswordTokenTimeCreated;
    }

    public function getResetPasswordTokenTimeExpires(): ?CarbonImmutable
    {
        return $this->resetPasswordTokenTimeExpires;
    }

    public function setResetPasswordTokenTimeExpires(?CarbonImmutable $resetPasswordTokenTimeExpires): void
    {
        $this->resetPasswordTokenTimeExpires = $resetPasswordTokenTimeExpires;
    }

    /**
     * @return Collection<int, APIKey>
     */
    public function getAPIKeys(): Collection
    {
        return $this->apiKeys;
    }

    public function setCurrentApiKey(APIKey $apiKey): void
    {
        $this->currentApiKey = $apiKey;
    }

    public function getCurrentApiKey(): ?APIKey
    {
        return $this->currentApiKey;
    }

    #[Override]
    public function getPrivateKey(): string
    {
        if (!$this->currentApiKey) {
            throw new RuntimeException('No API key is associated with the user.');
        }

        return $this->currentApiKey->getPrivateKey()->toString();
    }

    public function isCustomSorting(): bool
    {
        return $this->customSorting;
    }

    public function setCustomSorting(bool $customSorting): self
    {
        $this->customSorting = $customSorting;

        return $this;
    }

    /**
     * @return Collection<int, CustomSortingGroup>
     */
    public function getCustomSortingGroups(): Collection
    {
        return $this->customSortingGroups;
    }

    public function getCameraSessionData(): ?CameraSessionData
    {
        return $this->cameraSessionData;
    }

    public function setCameraSessionData(?CameraSessionData $cameraSessionData): self
    {
        $this->cameraSessionData = $cameraSessionData;

        return $this;
    }

    public function isCarEnterExitAllowed(): bool
    {
        return $this->carEnterExitAllowed;
    }

    public function setCarEnterExitAllowed(bool $carEnterExitAllowed): self
    {
        $this->carEnterExitAllowed = $carEnterExitAllowed;

        return $this;
    }

    public function isCarEnterExitShow(): bool
    {
        return $this->carEnterExitShow;
    }

    public function setCarEnterExitShow(bool $carEnterExitShow): self
    {
        $this->carEnterExitShow = $carEnterExitShow;

        return $this;
    }

    public function isEmailVerified(): bool
    {
        return $this->emailVerified;
    }

    public function setEmailVerified(bool $emailVerified): self
    {
        $this->emailVerified = $emailVerified;

        return $this;
    }

    public function getEmailVerifiedTime(): ?CarbonImmutable
    {
        return $this->emailVerifiedTime;
    }

    public function setEmailVerifiedTime(?CarbonImmutable $emailVerifiedTime): self
    {
        $this->emailVerifiedTime = $emailVerifiedTime;

        return $this;
    }

    public function getLastEmailSentTime(): ?CarbonImmutable
    {
        return $this->lastEmailSentTime;
    }

    public function setLastEmailSentTime(?CarbonImmutable $lastEmailSentTime): static
    {
        $this->lastEmailSentTime = $lastEmailSentTime;

        return $this;
    }

    public function isDisableAutomaticallyDueToInactivity(): bool
    {
        return $this->disableAutomaticallyDueToInactivity;
    }

    public function setDisableAutomaticallyDueToInactivity(bool $disableAutomaticallyDueToInactivity): static
    {
        $this->disableAutomaticallyDueToInactivity = $disableAutomaticallyDueToInactivity;

        return $this;
    }

    public function isPhoneVerified(): bool
    {
        return $this->phoneVerified;
    }

    public function setPhoneVerified(bool $phoneVerified): self
    {

        $this->phoneVerified = $phoneVerified;

        return $this;
    }

    public function getPhoneCountryPrefix(): int
    {
        return $this->phoneCountryPrefix;
    }

    public function setPhoneCountryPrefix(int $phoneCountryPrefix): self
    {
        if (!empty($this->phoneCountryPrefix) && $this->phoneCountryPrefix !== $phoneCountryPrefix) {
            $this->phoneVerified = false;
        }

        $this->phoneCountryPrefix = $phoneCountryPrefix;

        return $this;
    }

    public function getLandlord(): ?self
    {
        return $this->landlord;
    }

    public function setLandlord(?self $landlord): static
    {
        $this->landlord = $landlord;

        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getTenants(): Collection
    {
        return $this->tenants;
    }

    public function addTenant(self $tenant): static
    {
        if (!$this->tenants->contains($tenant)) {
            $this->tenants->add($tenant);
            $tenant->setLandlord($this);
        }

        return $this;
    }

    public function removeTenant(self $tenant): static
    {
        if ($this->tenants->removeElement($tenant)) {
            // set the owning side to null (unless already changed)
            if ($tenant->getLandlord() === $this) {
                $tenant->setLandlord(null);
            }
        }

        return $this;
    }

    public function isTenant(): bool
    {
        return $this->role === UserRole::TENANT;
    }

    public function getTimeTosAccepted(): ?CarbonImmutable
    {
        return $this->timeTosAccepted;
    }

    public function setTimeTosAccepted(?CarbonImmutable $timeTosAccepted): static
    {
        $this->timeTosAccepted = $timeTosAccepted;

        return $this;
    }

    private function hasSyncAttribute(ReflectionProperty $property): bool
    {
        return count($property->getAttributes(SyncWithLandlord::class)) > 0;
    }
}

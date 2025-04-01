<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Enum\DockontrolNodeStatus;
use App\Repository\DockontrolNodeRepository;
use App\Security\User\ApiKeyPairAuthenticatedUserInterface;
use App\Validator as CustomAssert;
use Carbon\CarbonImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Override;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: DockontrolNodeRepository::class)]
#[ORM\Table(name: 'dockontrol_nodes')]
#[CustomAssert\WireguardKeyPair(groups: ['pair_validation'])]
#[Assert\GroupSequence(['DockontrolNode', 'key_validation', 'pair_validation'])]
#[ORM\Index(columns: ['status'])]
class DockontrolNode implements HasWireguardKeyPairInterface, ApiKeyPairAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(options: ['unsigned' => true])]
    private int $id;

    #[ORM\Column(length: 63)]
    private string $name;

    #[ORM\Column(type: 'string', length: 63)]
    private string $ip;

    #[ORM\Column(nullable: true)]
    private ?CarbonImmutable $lastCommandExecutedTime = null;

    #[ORM\Column(options: ['default' => DockontrolNodeStatus::OFFLINE])]
    private DockontrolNodeStatus $status = DockontrolNodeStatus::OFFLINE;

    #[ORM\Column(type: 'float', nullable: true, options: ['precision' => 6, 'scale' => 2])]
    private ?float $ping = null;

    #[ORM\Column(nullable: true)]
    private ?CarbonImmutable $lastPingTime = null;

    #[ORM\Column(length: 63, nullable: true, options: ['default' => ''])]
    private ?string $dockontrolNodeVersion = null;

    #[ORM\Column(nullable: true)]
    private ?CarbonImmutable $lastMonitorCheckTime = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $comment = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $kernelVersion = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $osVersion = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $dockerVersion = null;

    #[ORM\Column(type: 'bigint', nullable: true, options: ['unsigned' => true])]
    private ?int $uptime = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $device = null;

    #[ORM\Column(type: 'uuid', unique: true, nullable: false)]
    private Uuid $apiPublicKey;

    #[ORM\Column(type: 'uuid', nullable: false)]
    private Uuid $apiSecretKey;

    #[ORM\Column(type: 'string', length: 44, nullable: false)]
    #[CustomAssert\WireguardKey('public', groups: ['key_validation'])]
    private string $wireguardPublicKey;

    #[ORM\Column(type: 'string', length: 44, nullable: false)]
    #[CustomAssert\WireguardKey('private', groups: ['key_validation'])]
    private string $wireguardPrivateKey;

    /**
     * @var Collection<int, CameraBackup>
     */
    #[ORM\OneToMany(targetEntity: CameraBackup::class, mappedBy: 'dockontrolNode')]
    private Collection $cameraBackups;

    /**
     * @var Collection<int, ActionBackupDockontrolNode>
     */
    #[ORM\OneToMany(targetEntity: ActionBackupDockontrolNode::class, mappedBy: 'dockontrolNode', fetch: 'LAZY')]
    private Collection $actionBackupDockontrolNodes;

    #[ORM\Column(nullable: false, options: ['default' => true])]
    private bool $notifyWhenStatusChange = true;

    /**
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class)]
    #[ORM\JoinTable(
        name: 'dockontrol_node_users_to_notify_when_status_change',
        joinColumns: [new ORM\JoinColumn(name: 'dockontrol_node_id', referencedColumnName: 'id')],
        inverseJoinColumns: [new ORM\JoinColumn('user_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    )]
    private Collection $usersToNotifyWhenStatusChanges;

    #[ORM\ManyToOne(inversedBy: 'dockontrolNodes')]
    #[ORM\JoinColumn(nullable: false)]
    private Building $building;

    #[ORM\Column(nullable: false, options: ['default' => true])]
    private bool $enabled = true;

    #[ORM\Column(type: 'integer', nullable: false, options: ['unsigned' => true, 'default' => 0])]
    #[Assert\Range(min: 0, max: 5)]
    private int $failCount = 0;

    #[ORM\Column(nullable: true)]
    private ?DockontrolNodeStatus $lastNotifyStatus = null;

    public function __construct()
    {
        $this->apiPublicKey = Uuid::v4();
        $this->apiSecretKey = Uuid::v4();
        $this->cameraBackups = new ArrayCollection();
        $this->actionBackupDockontrolNodes = new ArrayCollection();
        $this->usersToNotifyWhenStatusChanges = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
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

    public function getIp(): string
    {
        return $this->ip;
    }

    public function setIp(string $ip): self
    {
        $this->ip = $ip;

        return $this;
    }

    public function getLastCommandExecutedTime(): ?CarbonImmutable
    {
        return $this->lastCommandExecutedTime;
    }

    public function setLastCommandExecutedTime(?CarbonImmutable $lastCommandExecutedTime = null): self
    {
        $this->lastCommandExecutedTime = $lastCommandExecutedTime;

        return $this;
    }

    public function getStatus(): DockontrolNodeStatus
    {
        return $this->status;
    }

    public function setStatus(DockontrolNodeStatus $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getPing(): ?float
    {
        return $this->ping;
    }

    public function setPing(?float $ping = null): self
    {
        $this->ping = $ping;

        return $this;
    }

    public function getLastPingTime(): ?CarbonImmutable
    {
        return $this->lastPingTime;
    }

    public function setLastPingTime(?CarbonImmutable $lastPingTime): self
    {
        $this->lastPingTime = $lastPingTime;

        return $this;
    }

    public function getDockontrolNodeVersion(): ?string
    {
        return $this->dockontrolNodeVersion;
    }

    public function setDockontrolNodeVersion(?string $dockontrolNodeVersion = null): self
    {
        $this->dockontrolNodeVersion = $dockontrolNodeVersion;

        return $this;
    }

    public function getLastMonitorCheckTime(): ?CarbonImmutable
    {
        return $this->lastMonitorCheckTime;
    }

    public function setLastMonitorCheckTime(?CarbonImmutable $lastMonitorCheckTime): self
    {
        $this->lastMonitorCheckTime = $lastMonitorCheckTime;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): self
    {
        $this->comment = $comment;

        return $this;
    }

    public function getKernelVersion(): ?string
    {
        return $this->kernelVersion;
    }

    public function setKernelVersion(?string $kernelVersion): self
    {
        $this->kernelVersion = $kernelVersion;

        return $this;
    }

    public function getOsVersion(): ?string
    {
        return $this->osVersion;
    }

    public function setOsVersion(?string $osVersion): self
    {
        $this->osVersion = $osVersion;

        return $this;
    }

    public function getDockerVersion(): ?string
    {
        return $this->dockerVersion;
    }

    public function setDockerVersion(?string $dockerVersion): self
    {
        $this->dockerVersion = $dockerVersion;

        return $this;
    }

    public function getUptime(): ?int
    {
        return $this->uptime;
    }

    public function setUptime(?int $uptime): self
    {
        $this->uptime = $uptime;

        return $this;
    }

    public function getDevice(): ?string
    {
        return $this->device;
    }

    public function setDevice(?string $device): self
    {
        $this->device = $device;

        return $this;
    }

    public function getApiPublicKey(): Uuid
    {
        return $this->apiPublicKey;
    }

    public function setApiPublicKey(Uuid $apiPublicKey): self
    {
        $this->apiPublicKey = $apiPublicKey;

        return $this;
    }

    public function getApiSecretKey(): Uuid
    {
        return $this->apiSecretKey;
    }

    public function setApiSecretKey(Uuid $apiSecretKey): self
    {
        $this->apiSecretKey = $apiSecretKey;

        return $this;
    }

    #[Override]
    public function getWireguardPublicKey(): string
    {
        return $this->wireguardPublicKey;
    }

    public function setWireguardPublicKey(string $wireguardPublicKey): self
    {
        $this->wireguardPublicKey = $wireguardPublicKey;

        return $this;
    }

    #[Override]
    public function getWireguardPrivateKey(): string
    {
        return $this->wireguardPrivateKey;
    }

    public function setWireguardPrivateKey(string $wireguardPrivateKey): self
    {
        $this->wireguardPrivateKey = $wireguardPrivateKey;

        return $this;
    }

    /**
     * @return array<string>
     */
    #[Override]
    public function getRoles(): array
    {
        return ['ROLE_DOCKONTROL_NODE'];
    }

    #[Override]
    public function eraseCredentials(): void
    {
    }

    #[Override]
    public function getUserIdentifier(): string
    {
        /** @var non-empty-string $publicApiKey*/
        $publicApiKey = $this->apiPublicKey->toString();

        return $publicApiKey;
    }

    #[Override]
    public function getPrivateKey(): string
    {
        return $this->getApiSecretKey()->toString();
    }

    /**
     * @return Collection<int, CameraBackup>
     */
    public function getCameraBackups(): Collection
    {
        return $this->cameraBackups;
    }

    public function addCameraBackup(CameraBackup $cameraBackup): static
    {
        if (!$this->cameraBackups->contains($cameraBackup)) {
            $this->cameraBackups->add($cameraBackup);
            $cameraBackup->setDockontrolNode($this);
        }

        return $this;
    }

    public function getActionBackupDockontrolNodes(): Collection
    {
        return $this->actionBackupDockontrolNodes;
    }

    public function addActionBackupDockontrolNode(ActionBackupDockontrolNode $actionBackupDockontrolNode): static
    {
        if (!$this->actionBackupDockontrolNodes->contains($actionBackupDockontrolNode)) {
            $this->actionBackupDockontrolNodes->add($actionBackupDockontrolNode);
            $actionBackupDockontrolNode->setDockontrolNode($this);
        }

        return $this;
    }

    public function removeCameraBackup(CameraBackup $cameraBackup): static
    {
        $this->cameraBackups->removeElement($cameraBackup);

        return $this;
    }

    public function removeActionBackupDockontrolNode(ActionBackupDockontrolNode $actionBackupDockontrolNode): static
    {
        $this->actionBackupDockontrolNodes->removeElement($actionBackupDockontrolNode);

        return $this;
    }

    public function isNotifyWhenStatusChange(): bool
    {
        return $this->notifyWhenStatusChange;
    }

    public function setNotifyWhenStatusChange(bool $notifyWhenStatusChange): static
    {
        $this->notifyWhenStatusChange = $notifyWhenStatusChange;

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getUsersToNotifyWhenStatusChanges(): Collection
    {
        return $this->usersToNotifyWhenStatusChanges;
    }

    public function addUserToNotifyWhenStatusChanges(User $userToNotifyWhenStatusChanges): static
    {
        if (!$this->usersToNotifyWhenStatusChanges->contains($userToNotifyWhenStatusChanges)) {
            $this->usersToNotifyWhenStatusChanges->add($userToNotifyWhenStatusChanges);
        }

        return $this;
    }

    public function removeUserToNotifyWhenStatusChange(User $userToNotifyWhenStatusChanges): static
    {
        $this->usersToNotifyWhenStatusChanges->removeElement($userToNotifyWhenStatusChanges);

        return $this;
    }

    public function getBuilding(): Building
    {
        return $this->building;
    }

    public function setBuilding(Building $building): static
    {
        $this->building = $building;

        return $this;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): static
    {
        $this->enabled = $enabled;

        return $this;
    }

    public function getFailCount(): int
    {
        return $this->failCount;
    }

    public function setFailCount(int $failCount): self
    {
        $this->failCount = $failCount;

        return $this;
    }

    public function getLastNotifyStatus(): ?DockontrolNodeStatus
    {
        return $this->lastNotifyStatus;
    }

    public function setLastNotifyStatus(?DockontrolNodeStatus $lastNotifyStatus): self
    {
        $this->lastNotifyStatus = $lastNotifyStatus;

        return $this;
    }
}

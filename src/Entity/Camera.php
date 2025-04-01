<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\CameraRepository;
use App\Validator as CustomAssert;
use Carbon\CarbonImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Override;

#[ORM\Entity(repositoryClass: CameraRepository::class)]
#[ORM\Index(name: 'cameras_permissions_name_fk', columns: ['permission_required'])]
#[ORM\Table(name: 'cameras')]
class Camera implements CameraInterface
{
    #[ORM\Id]
    #[ORM\Column(length: 63)]
    private string $nameId;

    #[ORM\Column(nullable: false)]
    private string $friendlyName;

    #[ORM\Column(nullable: true)]
    private ?CarbonImmutable $lastFetched = null;

    #[ORM\ManyToOne(targetEntity: Permission::class, fetch: 'EAGER', inversedBy: 'cameras')]
    #[ORM\JoinColumn(name:'permission_required', referencedColumnName: 'name', nullable: true)]
    private ?Permission $permissionRequired = null;

    #[ORM\ManyToOne(targetEntity: DockontrolNode::class)]
    private DockontrolNode $dockontrolNode;

    /**
     * @var array{protocol: string, host: string, login: string, channel: string} $dockontrolNodePayload
     */
    #[ORM\Column(type: 'json', nullable: false)]
    #[CustomAssert\DockontrolNodeCameraPayload]
    private array $dockontrolNodePayload;

    /**
     * @var Collection<int, CameraBackup>
     */
    #[ORM\OneToMany(targetEntity: CameraBackup::class, mappedBy: 'parentCamera', fetch: 'LAZY')]
    private Collection $cameraBackups;

    public function __construct()
    {
        $this->cameraBackups = new ArrayCollection();
    }

    public function getNameId(): string
    {
        return $this->nameId;
    }

    public function setNameId(string $nameId): self
    {
        $this->nameId = $nameId;

        return $this;
    }

    public function getLastFetched(): ?CarbonImmutable
    {
        return $this->lastFetched;
    }

    public function setLastFetched(?CarbonImmutable $lastFetched): self
    {
        $this->lastFetched = $lastFetched;

        return $this;
    }

    public function getPermissionRequired(): ?Permission
    {
        return $this->permissionRequired;
    }

    public function setPermissionRequired(?Permission $permissionRequired): self
    {
        $this->permissionRequired = $permissionRequired;

        return $this;
    }

    #[Override]
    public function getDockontrolNode(): DockontrolNode
    {
        return $this->dockontrolNode;
    }

    public function setDockontrolNode(DockontrolNode $dockontrolNode): self
    {
        $this->dockontrolNode = $dockontrolNode;

        return $this;
    }

    /**
     * @return array{protocol: string, host: string, login: string, channel: string}
     */
    #[Override]
    public function getDockontrolNodePayload(): array
    {
        return $this->dockontrolNodePayload;
    }

    /**
     * @param array{protocol: string, host: string, login: string, channel: string} $dockontrolNodePayload
     */
    public function setDockontrolNodePayload(array $dockontrolNodePayload): self
    {
        $this->dockontrolNodePayload = $dockontrolNodePayload;

        return $this;
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
            $cameraBackup->setParentCamera($this);
        }

        return $this;
    }

    public function removeCameraBackup(CameraBackup $cameraBackup): static
    {
        $this->cameraBackups->removeElement($cameraBackup);

        return $this;
    }

    public function getFriendlyName(): string
    {
        return $this->friendlyName;
    }

    public function setFriendlyName(string $friendlyName): self
    {
        $this->friendlyName = $friendlyName;

        return $this;
    }
}

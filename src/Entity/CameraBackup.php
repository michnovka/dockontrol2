<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\CameraBackupRepository;
use App\Validator as CustomAssert;
use Doctrine\ORM\Mapping as ORM;
use Override;

#[ORM\Entity(repositoryClass: CameraBackupRepository::class)]
#[ORM\UniqueConstraint(
    name: 'camera_backup_unique',
    columns: ['parent_camera_name', 'dockontrol_node_id'],
)]
class CameraBackup implements CameraInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\ManyToOne(inversedBy: 'cameraBackups')]
    #[ORM\JoinColumn(name: 'parent_camera_name', referencedColumnName: 'name_id', nullable: false)]
    private Camera $parentCamera;

    #[ORM\ManyToOne(inversedBy: 'cameraBackups')]
    #[ORM\JoinColumn(nullable: false)]
    private DockontrolNode $dockontrolNode;

    /**
     * @var array{protocol: string, host: string, login: string, channel: string} $dockontrolNodePayload
     */
    #[ORM\Column(type: 'json', nullable: false)]
    #[CustomAssert\DockontrolNodeCameraPayload]
    private array $dockontrolNodePayload;

    public function getId(): int
    {
        return $this->id;
    }

    public function getParentCamera(): Camera
    {
        return $this->parentCamera;
    }

    public function setParentCamera(Camera $parentCamera): static
    {
        $this->parentCamera = $parentCamera;

        return $this;
    }

    #[Override]
    public function getDockontrolNode(): DockontrolNode
    {
        return $this->dockontrolNode;
    }

    public function setDockontrolNode(DockontrolNode $dockontrolNode): static
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
}

<?php

declare(strict_types=1);

namespace App\Entity\Log;

use App\Entity\Camera;
use App\Entity\User;
use App\Repository\Log\CameraLogRepository;
use Carbon\CarbonImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CameraLogRepository::class)]
#[ORM\Index(name: 'camera_log_time_index', columns: ['time'])]
#[ORM\Index(name: 'camera_log_users_id_fk', columns: ['user_id'])]
#[ORM\Index(name: 'camera_log_cameras_name_id_fk', columns: ['camera_name_id'])]
#[ORM\Table(name: 'camera_logs')]
class CameraLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'bigint')]
    private int $id;

    #[ORM\Column(type: 'datetime_immutable')]
    private CarbonImmutable $time;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'cascade')]
    private User $user;

    #[ORM\ManyToOne(targetEntity: Camera::class)]
    #[ORM\JoinColumn(name: 'camera_name_id', referencedColumnName: 'name_id', nullable: false, onDelete: 'cascade')]
    private Camera $camera;

    public function getId(): int
    {
        return $this->id;
    }

    public function getTime(): CarbonImmutable
    {
        return $this->time;
    }

    public function setTime(CarbonImmutable $time): self
    {
        $this->time = $time;

        return $this;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getCamera(): Camera
    {
        return $this->camera;
    }

    public function setCamera(Camera $camera): self
    {
        $this->camera = $camera;

        return $this;
    }
}

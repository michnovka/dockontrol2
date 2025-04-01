<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\UserDeletionRequestRepository;
use Carbon\CarbonImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserDeletionRequestRepository::class)]
class UserDeletionRequest
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\Column(nullable: false)]
    private CarbonImmutable $time;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(unique: true, nullable: false, onDelete: 'CASCADE')]
    private User $user;

    public function getId(): int
    {
        return $this->id;
    }

    public function getTime(): CarbonImmutable
    {
        return $this->time;
    }

    public function setTime(CarbonImmutable $time): static
    {
        $this->time = $time;

        return $this;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;

        return $this;
    }
}

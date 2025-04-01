<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\WebauthnRegistrationRepository;
use Carbon\CarbonImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WebauthnRegistrationRepository::class)]
#[ORM\Index(name: 'webauthn_registrations_users_id_fk', columns: ['user_id'])]
#[ORM\Table(name: 'webauthn_registrations')]
class WebauthnRegistration
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'bigint')]
    private int $id;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'cascade')]
    private User $user;

    #[ORM\Column(type: 'blob_string')]
    private string $data;

    #[ORM\Column(length: 255)]
    private string $credentialId;

    #[ORM\Column]
    private CarbonImmutable $createdTime;

    #[ORM\Column]
    private CarbonImmutable $lastUsedTime;

    public function getId(): int
    {
        return $this->id;
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

    public function getData(): string
    {
        return $this->data;
    }

    public function setData(string $data): self
    {
        $this->data = $data;

        return $this;
    }

    public function getCredentialId(): string
    {
        return $this->credentialId;
    }

    public function setCredentialId(string $credentialId): self
    {
        $this->credentialId = $credentialId;

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

    public function getLastUsedTime(): CarbonImmutable
    {
        return $this->lastUsedTime;
    }

    public function setLastUsedTime(CarbonImmutable $lastUsedTime): self
    {
        $this->lastUsedTime = $lastUsedTime;

        return $this;
    }
}

<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\APIKeyRepository;
use Carbon\CarbonImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: APIKeyRepository::class)]
#[ORM\Table(name: 'api_keys')]
class APIKey
{
    public const int MAX_API_KEYS_PER_USER = 100;

    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $publicKey;

    #[ORM\Column(type: 'uuid')]
    private Uuid $privateKey;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'apiKeys')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'cascade')]
    private User $user;

    #[ORM\Column]
    private CarbonImmutable $timeCreated;

    #[ORM\Column(nullable: true)]
    private ?CarbonImmutable $timeLastUsed = null;

    #[ORM\Column(length: 255)]
    private string $name;

    public function __construct()
    {
        $this->publicKey = Uuid::v4();
        $this->privateKey = Uuid::v4();
        $this->timeCreated = CarbonImmutable::now();
    }

    public function getPublicKey(): Uuid
    {
        return $this->publicKey;
    }

    public function setPublicKey(Uuid $publicKey): static
    {
        $this->publicKey = $publicKey;

        return $this;
    }

    public function getPrivateKey(): Uuid
    {
        return $this->privateKey;
    }

    public function setPrivateKey(Uuid $privateKey): static
    {
        $this->privateKey = $privateKey;

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

    public function getTimeCreated(): CarbonImmutable
    {
        return $this->timeCreated;
    }

    public function getTimeLastUsed(): ?CarbonImmutable
    {
        return $this->timeLastUsed;
    }

    public function setTimeLastUsed(?CarbonImmutable $timeLastUsed): static
    {
        $this->timeLastUsed = $timeLastUsed;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }
}

<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\SignupCodeRepository;
use Carbon\CarbonImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: SignupCodeRepository::class)]
#[ORM\Table(name: 'signup_codes')]
class SignupCode
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', length: 32)]
    private Uuid $hash;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'admin_id', nullable: false, onDelete: 'cascade')]
    private User $adminUser;

    #[ORM\Column(nullable: false)]
    private CarbonImmutable $expires;

    #[ORM\Column]
    private CarbonImmutable $createdTime;

    #[ORM\ManyToOne(targetEntity: Apartment::class, fetch: 'EAGER')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Apartment $apartment;

    #[ORM\ManyToOne(targetEntity: User::class, fetch: 'EAGER')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?User $newUser = null;

    #[ORM\Column(nullable: true)]
    private ?CarbonImmutable $usedTime = null;

    public function __construct()
    {
        $this->hash = Uuid::v7();
        $this->createdTime = CarbonImmutable::now();
        $this->expires = CarbonImmutable::now()->addDays(7);
    }

    public function getHash(): Uuid
    {
        return $this->hash;
    }

    public function setHash(Uuid $hash): self
    {
        $this->hash = $hash;

        return $this;
    }

    public function getAdminUser(): User
    {
        return $this->adminUser;
    }

    public function setAdminUser(User $adminUser): self
    {
        $this->adminUser = $adminUser;

        return $this;
    }

    public function getApartment(): Apartment
    {
        return $this->apartment;
    }

    public function setApartment(Apartment $apartment): self
    {
        $this->apartment = $apartment;

        return $this;
    }

    public function getExpires(): CarbonImmutable
    {
        return $this->expires;
    }

    public function setExpires(CarbonImmutable $expires): self
    {
        $this->expires = $expires;

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

    public function getNewUser(): ?User
    {
        return $this->newUser;
    }

    public function setNewUser(?User $newUser): self
    {
        $this->newUser = $newUser;

        return $this;
    }

    public function getUsedTime(): ?CarbonImmutable
    {
        return $this->usedTime;
    }

    public function setUsedTime(?CarbonImmutable $usedTime): self
    {
        $this->usedTime = $usedTime;

        return $this;
    }

    public function isUsable(): bool
    {
        return ($this->expires < CarbonImmutable::now() || $this->getNewUser() instanceof User);
    }

    public function isExpired(): bool
    {
        return $this->expires < CarbonImmutable::now();
    }
}

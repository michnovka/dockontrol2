<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\NukiRepository;
use Doctrine\ORM\Mapping as ORM;
use SensitiveParameter;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: NukiRepository::class)]
#[ORM\Index(name: 'nuki_users_id_fk', columns: ['user_id'])]
class Nuki
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'nukis')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'cascade')]
    private User $user;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(name: 'dockontrol_nuki_api_server', length: 255)]
    #[Assert\Url(message: 'Nuki API server url is not valid.', protocols: ['https'])]
    private string $dockontrolNukiApiServer;

    #[ORM\Column(length: 255)]
    private string $username;

    #[ORM\Column(length: 255)]
    private string $password1;

    #[ORM\Column(options: ['default' => false])]
    private bool $canLock = false;

    #[ORM\Column(type: 'string', length: 8, nullable: true)]
    #[Assert\Regex(
        pattern: '/^\d{4,8}$/',
        message: 'Nuki PIN must be between 4 and 8 digits.'
    )]
    private ?string $pin = null;

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

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getDockontrolNukiApiServer(): string
    {
        return $this->dockontrolNukiApiServer;
    }

    public function setDockontrolNukiApiServer(string $dockontrolNukiApiServer): self
    {
        $this->dockontrolNukiApiServer = $dockontrolNukiApiServer;

        return $this;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    public function getPassword1(): string
    {
        return $this->password1;
    }

    public function setPassword1(#[SensitiveParameter] string $password1): self
    {
        $this->password1 = $password1;

        return $this;
    }

    public function isCanLock(): bool
    {
        return $this->canLock;
    }

    public function setCanLock(bool $canLock): self
    {
        $this->canLock = $canLock;

        return $this;
    }

    public function getPin(): ?string
    {
        return $this->pin;
    }

    public function setPin(?string $pin): self
    {
        $this->pin = $pin;

        return $this;
    }
}

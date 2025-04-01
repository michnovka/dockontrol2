<?php

declare(strict_types=1);

namespace App\Entity\Log;

use App\Entity\User;
use App\Repository\Log\LoginLogRepository;
use Carbon\CarbonImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LoginLogRepository::class)]
#[ORM\Index(name: 'login_logs_users_id_fk', columns: ['user_id'])]
#[ORM\Table(name: 'login_logs')]
class LoginLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'bigint')]
    private int $id;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'cascade')]
    private User $user;

    #[ORM\Column(type: 'string', length: 255)]
    private string $ip;

    #[ORM\Column(length: 255)]
    private string $browser;

    #[ORM\Column(length: 255)]
    private string $platform;

    #[ORM\Column(options: ['default' => false])]
    private bool $fromRememberMe = false;

    #[ORM\Column(type: 'datetime_immutable')]
    private CarbonImmutable $time;

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

    public function getIp(): string
    {
        return $this->ip;
    }

    public function setIp(string $ip): self
    {
        $this->ip = $ip;

        return $this;
    }

    public function getBrowser(): string
    {
        return $this->browser;
    }

    public function setBrowser(string $browser): self
    {
        $this->browser = $browser;

        return $this;
    }

    public function getPlatform(): string
    {
        return $this->platform;
    }

    public function setPlatform(string $platform): self
    {
        $this->platform = $platform;

        return $this;
    }

    public function isFromRememberMe(): bool
    {
        return $this->fromRememberMe;
    }

    public function setFromRememberMe(bool $fromRememberMe): self
    {
        $this->fromRememberMe = $fromRememberMe;

        return $this;
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
}

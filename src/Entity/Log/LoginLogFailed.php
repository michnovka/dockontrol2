<?php

declare(strict_types=1);

namespace App\Entity\Log;

use App\Repository\Log\LoginLogFailedRepository;
use Carbon\CarbonImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LoginLogFailedRepository::class)]
#[ORM\Index(name: 'login_logs_failed_ip_index', columns: ['ip'])]
#[ORM\Index(name: 'login_logs_failed_time_index', columns: ['time'])]
#[ORM\Index(name: 'login_logs_failed_email_index', columns: ['email'])]
#[ORM\Table(name: 'login_logs_failed')]
class LoginLogFailed
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'bigint')]
    private int $id;

    #[ORM\Column(nullable: false)]
    private string $email;

    #[ORM\Column(type: 'string', length: 64)]
    private string $ip;

    #[ORM\Column(length: 255)]
    private string $browser;

    #[ORM\Column(length: 255)]
    private string $platform;

    #[ORM\Column(type: 'datetime_immutable')]
    private CarbonImmutable $time;

    public function getId(): int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

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

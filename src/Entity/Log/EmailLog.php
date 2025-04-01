<?php

declare(strict_types=1);

namespace App\Entity\Log;

use App\Repository\Log\EmailLogRepository;
use Carbon\CarbonImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: EmailLogRepository::class)]
#[ORM\Index(name: 'email_log_time', columns: ['time'])]
class EmailLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\Column(nullable: false)]
    private CarbonImmutable $time;

    #[ORM\Column(length: 255, nullable: false)]
    #[Assert\Email]
    private string $email;

    #[ORM\Column(length: 255, nullable: false)]
    private string $subject;

    public function __construct()
    {
        $this->time = CarbonImmutable::now();
    }

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

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function setSubject(string $subject): static
    {
        $this->subject = $subject;

        return $this;
    }
}

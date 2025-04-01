<?php

declare(strict_types=1);

namespace App\Entity\Log;

use App\Entity\User;
use App\Repository\Log\EmailChangeLogRepository;
use Carbon\CarbonImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: EmailChangeLogRepository::class)]
class EmailChangeLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\Column]
    private CarbonImmutable $timeCreated;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(length: 150)]
    #[Assert\Email]
    private string $oldEmail;

    #[ORM\Column(length: 150)]
    #[Assert\Email]
    private string $newEmail;

    #[ORM\Column(nullable: true)]
    private ?CarbonImmutable $oldEmailConfirmedTime = null;

    #[ORM\Column(nullable: true)]
    private ?CarbonImmutable $newEmailConfirmedTime = null;

    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $oldEmailConfirmHash;

    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $newEmailConfirmHash;

    public function getId(): int
    {
        return $this->id;
    }

    public function getTimeCreated(): CarbonImmutable
    {
        return $this->timeCreated;
    }

    public function setTimeCreated(CarbonImmutable $timeCreated): static
    {
        $this->timeCreated = $timeCreated;

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

    public function getOldEmail(): string
    {
        return $this->oldEmail;
    }

    public function setOldEmail(string $oldEmail): static
    {
        $this->oldEmail = $oldEmail;

        return $this;
    }

    public function getNewEmail(): string
    {
        return $this->newEmail;
    }

    public function setNewEmail(string $newEmail): static
    {
        $this->newEmail = $newEmail;

        return $this;
    }

    public function getOldEmailConfirmedTime(): ?CarbonImmutable
    {
        return $this->oldEmailConfirmedTime;
    }

    public function setOldEmailConfirmedTime(?CarbonImmutable $oldEmailConfirmedTime): static
    {
        $this->oldEmailConfirmedTime = $oldEmailConfirmedTime;

        return $this;
    }

    public function getNewEmailConfirmedTime(): ?CarbonImmutable
    {
        return $this->newEmailConfirmedTime;
    }

    public function setNewEmailConfirmedTime(CarbonImmutable $newEmailConfirmedTime): static
    {
        $this->newEmailConfirmedTime = $newEmailConfirmedTime;

        return $this;
    }

    public function getOldEmailConfirmHash(): Uuid
    {
        return $this->oldEmailConfirmHash;
    }

    public function setOldEmailConfirmHash(Uuid $oldEmailConfirmHash): static
    {
        $this->oldEmailConfirmHash = $oldEmailConfirmHash;

        return $this;
    }

    public function getNewEmailConfirmHash(): Uuid
    {
        return $this->newEmailConfirmHash;
    }

    public function setNewEmailConfirmHash(Uuid $newEmailConfirmHash): static
    {
        $this->newEmailConfirmHash = $newEmailConfirmHash;

        return $this;
    }
}

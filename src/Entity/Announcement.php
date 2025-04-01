<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\AnnouncementRepository;
use Carbon\CarbonImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AnnouncementRepository::class)]
#[ORM\Index(name: 'announcement_start_time_index', columns: ['start_time'])]
#[ORM\Index(name: 'announcement_end_time_index', columns: ['end_time'])]
#[ORM\Index(name: 'announcement_start_time_and_end_time_index', columns: ['start_time', 'end_time'])]
class Announcement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\Column(nullable: true)]
    private ?CarbonImmutable $startTime = null;

    #[ORM\Column(nullable: true)]
    private ?CarbonImmutable $endTime = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?Building $building = null;

    #[ORM\Column]
    private CarbonImmutable $createdTime;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private User $createdBy;

    #[ORM\Column(length: 255)]
    private ?string $subject = null;

    #[ORM\Column(type: 'text')]
    private ?string $content = null;

    public function __construct()
    {
        $this->createdTime = new CarbonImmutable();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getStartTime(): ?CarbonImmutable
    {
        return $this->startTime;
    }

    public function setStartTime(?CarbonImmutable $startTime): self
    {
        $this->startTime = $startTime;

        return $this;
    }

    public function getEndTime(): ?CarbonImmutable
    {
        return $this->endTime;
    }

    public function setEndTime(?CarbonImmutable $endTime): self
    {
        $this->endTime = $endTime;

        return $this;
    }

    public function getBuilding(): ?Building
    {
        return $this->building;
    }

    public function setBuilding(?Building $building): self
    {
        $this->building = $building;

        return $this;
    }

    public function getCreatedTime(): ?CarbonImmutable
    {
        return $this->createdTime;
    }

    public function setCreatedTime(CarbonImmutable $createdTime): self
    {
        $this->createdTime = $createdTime;

        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(User $createdBy): self
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function setSubject(string $subject): self
    {
        $this->subject = $subject;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;

        return $this;
    }
}

<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Log\CronLog;
use App\Repository\ActionQueueCronGroupRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ActionQueueCronGroupRepository::class)]
class ActionQueueCronGroup
{
    #[ORM\Id]
    #[ORM\Column(length: 150, nullable: false)]
    private string $name;

    /** @var Collection<int, Action> $actions */
    #[ORM\OneToMany(targetEntity: Action::class, mappedBy: 'actionQueueCronGroup', fetch: 'LAZY')]
    private Collection $actions;

    /** @var Collection<int, CronLog> $cronLogs */
    #[ORM\OneToMany(targetEntity: CronLog::class, mappedBy: 'actionQueueCronGroup', fetch: 'LAZY')]
    private Collection $cronLogs;

    public function __construct()
    {
        $this->actions = new ArrayCollection();
        $this->cronLogs = new ArrayCollection();
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

    /**
     * @return Collection<int, Action>
     */
    public function getActions(): Collection
    {
        return $this->actions;
    }

    /**
     * @return Collection<int, CronLog>
     */
    public function getCronLogs(): Collection
    {
        return $this->cronLogs;
    }
}

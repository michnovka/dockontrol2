<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Enum\ActionQueueStatus;
use App\Repository\ActionQueueRepository;
use Carbon\CarbonImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: ActionQueueRepository::class)]
#[ORM\Index(name: 'queue_time_start_status_index', columns: ['time_start', 'status'])]
#[ORM\Index(name: 'queue_time_created_index', columns: ['time_created'])]
#[ORM\Index(name: 'action_queue_count_into_stats_index', columns: ['count_into_stats'])]
#[ORM\Index(name: 'action_queue_action_index', columns: ['action'])]
#[ORM\Index(name: 'action_queue_time_executed', columns: ['time_executed'])]
#[ORM\Index(name: 'queue_time_start_index', columns: ['time_start'])]
#[ORM\Index(name: 'queue_status_index', columns: ['status'])]
class ActionQueue
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'bigint')]
    private int $id;

    #[ORM\Column(nullable: false)]
    private CarbonImmutable $timeCreated;

    #[ORM\Column(nullable: false)]
    private CarbonImmutable $timeStart;

    #[ORM\Column(nullable: false, options: ['default' => ActionQueueStatus::QUEUED])]
    private ActionQueueStatus $status = ActionQueueStatus::QUEUED;

    #[ORM\ManyToOne(targetEntity: Action::class, inversedBy: 'actionQueues')]
    #[ORM\JoinColumn(name:'action', referencedColumnName: 'name', nullable: false)]
    private Action $action;

    #[ORM\ManyToOne(inversedBy: 'actionQueues')]
    #[ORM\JoinColumn(nullable: false, onDelete: "cascade")]
    private User $user;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(referencedColumnName: 'hash', onDelete: 'CASCADE')]
    private ?Guest $guest = null;

    #[ORM\Column(nullable: false, options: ['default' => true])]
    private bool $countIntoStats = true;

    #[ORM\Column(nullable: true)]
    private ?CarbonImmutable $timeExecuted = null;

    #[ORM\Column(nullable: false, options: ['default' => false])]
    private bool $isImmediate = false;

    #[Assert\Callback]
    public function validateTimeExecuted(ExecutionContextInterface $context): void
    {
        if ($this->status === ActionQueueStatus::EXECUTED && !$this->timeExecuted instanceof CarbonImmutable) {
            $context->buildViolation('timeExecuted can not be null.')
                ->atPath('timeExecuted')
                ->addViolation();
        } elseif ($this->status !== ActionQueueStatus::EXECUTED && $this->timeExecuted instanceof CarbonImmutable) {
            $context->buildViolation('timeExecuted must be null.')
                ->atPath('timeExecuted')
                ->addViolation();
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTimeCreated(): CarbonImmutable
    {
        return $this->timeCreated;
    }

    public function setTimeCreated(CarbonImmutable $timeCreated): self
    {
        $this->timeCreated = $timeCreated;

        return $this;
    }

    public function getTimeStart(): CarbonImmutable
    {
        return $this->timeStart;
    }

    public function setTimeStart(CarbonImmutable $timeStart): self
    {
        $this->timeStart = $timeStart;

        return $this;
    }

    public function getStatus(): ActionQueueStatus
    {
        return $this->status;
    }

    public function setStatus(ActionQueueStatus $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getAction(): Action
    {
        return $this->action;
    }

    public function setAction(Action $action): self
    {
        $this->action = $action;

        return $this;
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

    public function getGuest(): ?Guest
    {
        return $this->guest;
    }

    public function setGuest(?Guest $guest): self
    {
        $this->guest = $guest;

        return $this;
    }

    public function isCountIntoStats(): bool
    {
        return $this->countIntoStats;
    }

    public function setCountIntoStats(bool $countIntoStats): self
    {
        $this->countIntoStats = $countIntoStats;

        return $this;
    }

    public function getTimeExecuted(): ?CarbonImmutable
    {
        return $this->timeExecuted;
    }

    public function setTimeExecuted(?CarbonImmutable $timeExecuted): static
    {
        $this->timeExecuted = $timeExecuted;

        return $this;
    }

    public function isImmediate(): bool
    {
        return $this->isImmediate;
    }

    public function setIsImmediate(bool $isImmediate): static
    {
        $this->isImmediate = $isImmediate;

        return $this;
    }
}

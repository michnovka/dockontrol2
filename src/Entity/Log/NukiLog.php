<?php

declare(strict_types=1);

namespace App\Entity\Log;

use App\Entity\Enum\NukiAction;
use App\Entity\Enum\NukiStatus;
use App\Entity\Nuki;
use App\Repository\Log\NukiLogRepository;
use Carbon\CarbonImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NukiLogRepository::class)]
#[ORM\Index(name: 'nuki_logs_nuki_id_fk', columns: ['nuki_id'])]
#[ORM\Index(name: 'nuki_logs_status_index', columns: ['status'])]
#[ORM\Index(name: 'nuki_logs_time_index', columns: ['time'])]
#[ORM\Table(name: 'nuki_logs')]
class NukiLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'bigint')]
    private int $id;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'cascade')]
    private Nuki $nuki;

    #[ORM\Column(options: ['default' => NukiStatus::OK])]
    private NukiStatus $status = NukiStatus::OK;

    #[ORM\Column(options: ['default' => NukiAction::UNLOCK])]
    private NukiAction $action = NukiAction::UNLOCK;

    #[ORM\Column]
    private CarbonImmutable $time;

    public function getId(): int
    {
        return $this->id;
    }

    public function getNuki(): Nuki
    {
        return $this->nuki;
    }

    public function setNuki(Nuki $nuki): self
    {
        $this->nuki = $nuki;

        return $this;
    }

    public function getStatus(): NukiStatus
    {
        return $this->status;
    }

    public function setStatus(NukiStatus $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getAction(): NukiAction
    {
        return $this->action;
    }

    public function setAction(NukiAction $action): self
    {
        $this->action = $action;

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

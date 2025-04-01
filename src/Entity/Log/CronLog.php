<?php

declare(strict_types=1);

namespace App\Entity\Log;

use App\Entity\ActionQueueCronGroup;
use App\Entity\Enum\CronType;
use App\Repository\Log\CronLogRepository;
use Carbon\CarbonImmutable;
use Doctrine\ORM\Mapping as ORM;
use Override;
use Symfony\Component\Serializer\Normalizer\NormalizableInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

#[ORM\Entity(repositoryClass: CronLogRepository::class)]
#[ORM\Index(name: 'cron_group_time_start', columns: ['time_start'])]
#[ORM\Index(name: 'cron_group_time_end', columns: ['time_end'])]
class CronLog implements NormalizableInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\Column(nullable: false)]
    private CarbonImmutable $timeStart;

    #[ORM\Column(nullable: false)]
    private CarbonImmutable $timeEnd;

    #[ORM\Column(nullable: false)]
    private CronType $type;

    #[ORM\ManyToOne(targetEntity: ActionQueueCronGroup::class, inversedBy: 'cronLogs')]
    #[ORM\JoinColumn(name: 'cron_group', referencedColumnName: 'name', nullable: true, onDelete: 'cascade')]
    private ?ActionQueueCronGroup $actionQueueCronGroup = null;

    #[ORM\Column(type: 'text')]
    private string $output;

    #[ORM\Column(type: 'boolean', nullable: false, options: ['default' => true])]
    private bool $success = true;

    public function getId(): int
    {
        return $this->id;
    }

    public function getTimeStart(): CarbonImmutable
    {
        return $this->timeStart;
    }

    public function setTimeStart(CarbonImmutable $timeStart): static
    {
        $this->timeStart = $timeStart;

        return $this;
    }

    public function getTimeEnd(): CarbonImmutable
    {
        return $this->timeEnd;
    }

    public function setTimeEnd(CarbonImmutable $timeEnd): static
    {
        $this->timeEnd = $timeEnd;

        return $this;
    }

    public function getType(): CronType
    {
        return $this->type;
    }

    public function setType(CronType $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getActionQueueCronGroup(): ?ActionQueueCronGroup
    {
        return $this->actionQueueCronGroup;
    }

    public function setActionQueueCronGroup(?ActionQueueCronGroup $cronGroup): static
    {
        $this->actionQueueCronGroup = $cronGroup;

        return $this;
    }

    public function getOutput(): string
    {
        return $this->output;
    }

    public function setOutput(string $output): static
    {
        $this->output = $output;

        return $this;
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function setSuccess(bool $success): self
    {
        $this->success = $success;

        return $this;
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function normalize(
        NormalizerInterface $normalizer,
        ?string $format = null,
        array $context = [],
    ): array {
        return [
            'timeStart' => $this->getTimeStart()->unix(),
            'timeEnd' => $this->getTimeEnd()->unix(),
            'cronType' => $this->getType()->getReadable(),
            'cronGroup' => $this->getActionQueueCronGroup()?->getName(),
            'output' => $this->getOutput(),
        ];
    }
}

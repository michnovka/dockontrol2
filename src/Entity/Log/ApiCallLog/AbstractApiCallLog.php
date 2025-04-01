<?php

declare(strict_types=1);

namespace App\Entity\Log\ApiCallLog;

use App\Repository\Log\ApiCallLog\ApiCallLogRepository;
use Carbon\CarbonImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ApiCallLogRepository::class)]
#[ORM\InheritanceType('JOINED')]
#[ORM\DiscriminatorColumn(name: 'type', type: 'string')]
#[ORM\DiscriminatorMap([
    'legacy' => LegacyAPICallLog::class,
    'api2' => API2CallLog::class,
    'dockontrol_node' => DockontrolNodeAPICallLog::class,
])]
#[ORM\Index(name: 'api_calls_time_index', columns: ['time'])]
#[ORM\Index(name: 'api_calls_ip_index', columns: ['ip'])]
#[ORM\Table(name: 'api_call_logs')]
abstract class AbstractApiCallLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'bigint')]
    private int $id;

    #[ORM\Column(type: 'datetime_immutable')]
    private CarbonImmutable $time;

    #[ORM\Column(type: 'string', length: 63)]
    private string $ip;

    #[ORM\Column(length: 255)]
    private string $apiAction;

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

    public function getIp(): string
    {
        return $this->ip;
    }

    public function setIp(string $ip): self
    {
        $this->ip = $ip;

        return $this;
    }

    public function getApiAction(): string
    {
        return $this->apiAction;
    }

    public function setApiAction(string $apiAction): self
    {
        $this->apiAction = $apiAction;

        return $this;
    }
}

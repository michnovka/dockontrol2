<?php

declare(strict_types=1);

namespace App\Entity\Log\ApiCallFailedLog;

use App\Repository\Log\ApiCallFailedLog\ApiCallFailedLogRepository;
use Carbon\CarbonImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ApiCallFailedLogRepository::class)]
#[ORM\InheritanceType('JOINED')]
#[ORM\DiscriminatorColumn(name: 'type', type: 'string')]
#[ORM\DiscriminatorMap([
    'legacy' => LegacyAPICallFailedLog::class,
    'api2' => API2CallFailedLog::class,
    'dockontrol_node' => DockontrolNodeAPICallFailedLog::class,
])]
#[ORM\Index(name: 'api_calls_failed_time_index', columns: ['time'])]
#[ORM\Index(name: 'api_calls_failed_ip_index', columns: ['ip'])]
#[ORM\Table(name: 'api_call_failed_logs')]
abstract class AbstractApiCallFailedLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'bigint')]
    private int $id;

    #[ORM\Column(type: 'datetime_immutable')]
    private CarbonImmutable $time;

    #[ORM\Column(type: 'string', length: 64)]
    private string $ip;

    #[ORM\Column(type: 'string')]
    private string $apiEndpoint;

    #[ORM\Column(type: 'string')]
    private string $reason;

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

    public function getApiEndpoint(): string
    {
        return $this->apiEndpoint;
    }

    public function setApiEndpoint(string $apiEndpoint): self
    {
        $this->apiEndpoint = $apiEndpoint;

        return $this;
    }

    public function getReason(): string
    {
        return $this->reason;
    }

    public function setReason(string $reason): self
    {
        $this->reason = $reason;

        return $this;
    }
}

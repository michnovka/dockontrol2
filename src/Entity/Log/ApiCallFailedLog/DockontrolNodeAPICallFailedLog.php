<?php

declare(strict_types=1);

namespace App\Entity\Log\ApiCallFailedLog;

use App\Repository\Log\ApiCallFailedLog\DockontrolNodeAPICallFailedLogRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DockontrolNodeAPICallFailedLogRepository::class)]
#[ORM\Table(name: 'dockontrol_node_api_call_failed_logs')]
#[ORM\Index(name: 'dockontrol_node_api_call_failed_api_key_index', columns: ['dockontrol_node_api_key'])]
class DockontrolNodeAPICallFailedLog extends AbstractApiCallFailedLog
{
    #[ORM\Column(name: 'dockontrol_node_api_key', type: 'string')]
    private string $dockontrolNodeAPIKey;

    public function getDockontrolNodeAPIKey(): string
    {
        return $this->dockontrolNodeAPIKey;
    }

    public function setDockontrolNodeAPIKey(string $dockontrolNodeAPIKey): self
    {
        $this->dockontrolNodeAPIKey = $dockontrolNodeAPIKey;

        return $this;
    }
}

<?php

declare(strict_types=1);

namespace App\Entity\Log\ApiCallFailedLog;

use App\Repository\Log\ApiCallFailedLog\API2CallFailedLogRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: API2CallFailedLogRepository::class)]
#[ORM\Table(name: 'api2_call_failed_logs')]
#[ORM\Index(name: 'api2_call_failed_api_key_index', columns: ['api_key'])]
class API2CallFailedLog extends AbstractApiCallFailedLog
{
    #[ORM\Column(type: 'string')]
    private string $apiKey;

    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    public function setApiKey(string $apiKey): self
    {
        $this->apiKey = $apiKey;

        return $this;
    }
}

<?php

declare(strict_types=1);

namespace App\Entity\Log\ApiCallFailedLog;

use App\Repository\Log\ApiCallFailedLog\LegacyAPICallFailedLogRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LegacyAPICallFailedLogRepository::class)]
#[ORM\Table(name: 'legacy_api_call_failed_logs')]
#[ORM\Index(name: 'legacy_api_call_failed_email_index', columns: ['email'])]
class LegacyAPICallFailedLog extends AbstractApiCallFailedLog
{
    #[ORM\Column(type: 'string')]
    private string $email;

    #[ORM\Column(length: 255)]
    private string $apiAction;

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

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

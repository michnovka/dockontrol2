<?php

declare(strict_types=1);

namespace App\Entity\Log\ApiCallLog;

use App\Entity\User;
use App\Repository\Log\ApiCallLog\API2CallLogRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: API2CallLogRepository::class)]
#[ORM\Table(name: 'api2_call_logs')]
class API2CallLog extends AbstractApiCallLog
{
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'cascade')]
    private User $user;

    #[ORM\Column(nullable: false)]
    private string $apiKey;

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

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

<?php

declare(strict_types=1);

namespace App\Entity\Log\ApiCallLog;

use App\Entity\User;
use App\Repository\Log\ApiCallLog\LegacyAPICallLogRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LegacyAPICallLogRepository::class)]
#[ORM\Table(name: 'legacy_api_call_logs')]
class LegacyAPICallLog extends AbstractApiCallLog
{
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'cascade')]
    private User $user;

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }
}

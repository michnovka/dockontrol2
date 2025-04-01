<?php

declare(strict_types=1);

namespace App\Entity\Log\ApiCallLog;

use App\Entity\DockontrolNode;
use App\Repository\Log\ApiCallLog\DockontrolNodeAPICallLogRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DockontrolNodeAPICallLogRepository::class)]
#[ORM\Table(name: 'dockontrol_node_api_call_logs')]
class DockontrolNodeAPICallLog extends AbstractApiCallLog
{
    #[ORM\ManyToOne(targetEntity: DockontrolNode::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'cascade')]
    private DockontrolNode $dockontrolNode;

    public function getDockontrolNode(): DockontrolNode
    {
        return $this->dockontrolNode;
    }

    public function setDockontrolNode(DockontrolNode $dockControl): self
    {
        $this->dockontrolNode = $dockControl;

        return $this;
    }
}

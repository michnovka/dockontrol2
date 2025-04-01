<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Enum\ActionType;
use App\Repository\ActionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Override;

#[ORM\Entity(repositoryClass: ActionRepository::class)]
#[ORM\Index(name: 'actions_dockontrol_nodes_id_fk', columns: ['dockontrol_node_id'])]
#[ORM\Index(columns: ['action_queue_cron_group'])]
#[ORM\Table(name: 'actions')]
class Action implements ActionInterface
{
    #[ORM\Id]
    #[ORM\Column(length: 63, nullable: false)]
    private string $name;

    #[ORM\Column(nullable: false)]
    private string $friendlyName;

    #[ORM\Column(nullable: false)]
    private ActionType $type;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'dockontrol_node_id', options: ['nullable' => true])]
    private ?DockontrolNode $dockontrolNode = null;

    #[ORM\ManyToOne(targetEntity: ActionQueueCronGroup::class, fetch: 'LAZY', inversedBy: 'actions')]
    #[ORM\JoinColumn(name: 'action_queue_cron_group', referencedColumnName: 'name', nullable: false)]
    private ActionQueueCronGroup $actionQueueCronGroup;

    /**
     * @var array<string, mixed>|null $actionPayload
     */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $actionPayload = null;

    #[ORM\OneToMany(targetEntity: ActionQueue::class, mappedBy: 'action', fetch: 'LAZY')]
    private Collection $actionQueues;

    /**
     * @var Collection<int, ActionBackupDockontrolNode>
     */
    #[ORM\OneToMany(targetEntity: ActionBackupDockontrolNode::class, mappedBy: 'parentAction', fetch: 'LAZY')]
    private Collection $actionBackupDockontrolNodes;

    public function __construct()
    {
        $this->actionQueues = new ArrayCollection();
        $this->actionBackupDockontrolNodes = new ArrayCollection();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getType(): ActionType
    {
        return $this->type;
    }

    public function setType(ActionType $type): self
    {
        $this->type = $type;

        return $this;
    }

    #[Override]
    public function getDockontrolNode(): ?DockontrolNode
    {
        return $this->dockontrolNode;
    }

    public function setDockontrolNode(?DockontrolNode $dockontrolNode): self
    {
        $this->dockontrolNode = $dockontrolNode;

        return $this;
    }

    public function getActionQueueCronGroup(): ActionQueueCronGroup
    {
        return $this->actionQueueCronGroup;
    }

    public function setActionQueueCronGroup(ActionQueueCronGroup $cronGroup): self
    {
        $this->actionQueueCronGroup = $cronGroup;

        return $this;
    }

    /**
     * @return array<string, mixed>|null
     */
    #[Override]
    public function getActionPayload(): ?array
    {
        return $this->actionPayload;
    }

    /**
     * @param array<string, mixed>|null $actionPayload
     */
    public function setActionPayload(?array $actionPayload): self
    {
        $this->actionPayload = $actionPayload;

        return $this;
    }

    public function getActionQueues(): Collection
    {
        return $this->actionQueues;
    }

    /**
     * @return Collection<int, ActionBackupDockontrolNode>
     */
    public function getActionBackupDockontrolNodes(): Collection
    {
        return $this->actionBackupDockontrolNodes;
    }

    public function addActionBackupDockontrolNode(ActionBackupDockontrolNode $actionBackupDockontrolNode): static
    {
        if (!$this->actionBackupDockontrolNodes->contains($actionBackupDockontrolNode)) {
            $this->actionBackupDockontrolNodes->add($actionBackupDockontrolNode);
            $actionBackupDockontrolNode->setParentAction($this);
        }

        return $this;
    }

    public function removeActionBackupDockontrolNode(ActionBackupDockontrolNode $actionBackupDockontrolNode): static
    {
        $this->actionBackupDockontrolNodes->removeElement($actionBackupDockontrolNode);

        return $this;
    }

    public function getFriendlyName(): string
    {
        return $this->friendlyName;
    }

    public function setFriendlyName(string $friendlyName): self
    {
        $this->friendlyName = $friendlyName;

        return $this;
    }
}

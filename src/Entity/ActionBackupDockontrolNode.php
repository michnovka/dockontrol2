<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ActionBackupDockontrolNodeRepository;
use Doctrine\ORM\Mapping as ORM;
use Override;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: ActionBackupDockontrolNodeRepository::class)]
#[ORM\UniqueConstraint(
    name: 'action_backup_dockontrol_node_unique',
    columns: ['dockontrol_node_id', 'parent_action']
)]
class ActionBackupDockontrolNode implements ActionInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\ManyToOne(inversedBy: 'actionBackupDockontrolNodes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?DockontrolNode $dockontrolNode;

    /**
     * @var array<string, mixed> $actionPayload
     */
    #[ORM\Column(type: 'json', nullable: false)]
    private ?array $actionPayload;

    #[ORM\ManyToOne(inversedBy: 'actionBackupDockontrolNodes')]
    #[ORM\JoinColumn(name: 'parent_action', referencedColumnName: 'name', nullable: false)]
    private Action $parentAction;

    #[Assert\Callback]
    public function validateDockontrolNode(ExecutionContextInterface $context): void
    {
        if ($this->dockontrolNode === $this->parentAction->getDockontrolNode()) {
            $context->buildViolation('selected dockontrol node cannot be the same as the parent\'s dockontrol node.')
                ->atPath('dockontrolNode')
                ->addViolation();
        }
    }

    public function getId(): int
    {
        return $this->id;
    }

    #[Override]
    public function getDockontrolNode(): ?DockontrolNode
    {
        return $this->dockontrolNode;
    }

    public function setDockontrolNode(DockontrolNode $dockontrolNode): static
    {
        $this->dockontrolNode = $dockontrolNode;

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
     * @param array<string, mixed> $actionPayload
     */
    public function setActionPayload(array $actionPayload): self
    {
        $this->actionPayload = $actionPayload;

        return $this;
    }

    public function getParentAction(): Action
    {
        return $this->parentAction;
    }

    public function setParentAction(Action $parentAction): static
    {
        $this->parentAction = $parentAction;

        return $this;
    }
}

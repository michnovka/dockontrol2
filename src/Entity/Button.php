<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Enum\ButtonIcon;
use App\Entity\Enum\ButtonStyle;
use App\Entity\Enum\ButtonType;
use App\Repository\ButtonRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ButtonRepository::class)]
#[ORM\Index(name: 'buttons_type_index', columns: ['type'])]
#[ORM\Index(name: 'button_cameras_name_id_fk', columns: ['camera1'])]
#[ORM\Index(name: 'button_cameras_name_id_fk_2', columns: ['camera2'])]
#[ORM\Index(name: 'button_cameras_name_id_fk_3', columns: ['camera3'])]
#[ORM\Index(name: 'button_cameras_name_id_fk_4', columns: ['camera4'])]
#[ORM\Index(name: 'button_permission_index', columns: ['permission'])]
#[ORM\Index(name: 'buttons_order_index', columns: ['sort_index'])]
#[ORM\Index(name: 'buttons_actions_name_fk', columns: ['action'])]
#[ORM\Table(name: 'buttons')]
class Button
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 63)]
    private string $id;

    #[ORM\Column(length: 255, nullable: false, options: ['default' => ButtonType::ENTRANCE])]
    private ButtonType $type = ButtonType::ENTRANCE;

    #[ORM\ManyToOne(targetEntity: Action::class, fetch: 'EAGER')]
    #[ORM\JoinColumn(name:'action', referencedColumnName: 'name', nullable: false)]
    private Action $action;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $actionMulti = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $actionMultiDescription = null;

    #[ORM\Column(length: 63, nullable: false)]
    private string $name;

    #[ORM\Column(length: 63, nullable: true)]
    private ?string $nameSpecification = null;

    #[ORM\ManyToOne(targetEntity: Permission::class)]
    #[ORM\JoinColumn(name:'permission', referencedColumnName: 'name', nullable: true)]
    private ?Permission $permission = null;

    #[ORM\Column(name: 'allow_1min_open', nullable: false, options: ['default' => false])]
    private bool $allow1MinOpen = false;

    #[ORM\ManyToOne(targetEntity: Camera::class)]
    #[ORM\JoinColumn(name:'camera1', referencedColumnName: 'name_id', nullable: true)]
    private ?Camera $camera1 = null;

    #[ORM\ManyToOne(targetEntity: Camera::class)]
    #[ORM\JoinColumn(name:'camera2', referencedColumnName: 'name_id', nullable: true)]
    private ?Camera $camera2 = null;

    #[ORM\ManyToOne(targetEntity: Camera::class)]
    #[ORM\JoinColumn(name:'camera3', referencedColumnName: 'name_id', nullable: true)]
    private ?Camera $camera3 = null;

    #[ORM\ManyToOne(targetEntity: Camera::class)]
    #[ORM\JoinColumn(name:'camera4', referencedColumnName: 'name_id', nullable: true)]
    private ?Camera $camera4 = null;

    #[ORM\Column(nullable: false)]
    private int $sortIndex;

    #[ORM\Column(nullable: false, options: ['default' => ButtonStyle::BASIC])]
    private ButtonStyle $buttonStyle = ButtonStyle::BASIC;

    #[ORM\Column(nullable: false, options: ['default' => ButtonIcon::ENTRANCE])]
    private ButtonIcon $icon;

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getType(): ButtonType
    {
        return $this->type;
    }

    public function setType(ButtonType $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getAction(): Action
    {
        return $this->action;
    }

    public function setAction(Action $action): self
    {
        $this->action = $action;

        return $this;
    }

    public function getActionMulti(): ?string
    {
        return $this->actionMulti;
    }

    public function setActionMulti(?string $actionMulti): self
    {
        $this->actionMulti = $actionMulti;

        return $this;
    }

    public function getActionMultiDescription(): ?string
    {
        return $this->actionMultiDescription;
    }

    public function setActionMultiDescription(?string $actionMultiDescription): self
    {
        $this->actionMultiDescription = $actionMultiDescription;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getNameSpecification(): ?string
    {
        return $this->nameSpecification;
    }

    public function setNameSpecification(?string $nameSpecification): self
    {
        $this->nameSpecification = $nameSpecification;

        return $this;
    }

    public function getPermission(): ?Permission
    {
        return $this->permission;
    }

    public function setPermission(?Permission $permission): self
    {
        $this->permission = $permission;

        return $this;
    }

    public function isAllow1MinOpen(): bool
    {
        return $this->allow1MinOpen;
    }

    public function setAllow1MinOpen(bool $allow1MinOpen): self
    {
        $this->allow1MinOpen = $allow1MinOpen;

        return $this;
    }

    public function getCamera1(): ?Camera
    {
        return $this->camera1;
    }

    public function setCamera1(?Camera $camera1): self
    {
        $this->camera1 = $camera1;

        return $this;
    }

    public function getCamera2(): ?Camera
    {
        return $this->camera2;
    }

    public function setCamera2(?Camera $camera2): self
    {
        $this->camera2 = $camera2;

        return $this;
    }

    public function getCamera3(): ?Camera
    {
        return $this->camera3;
    }

    public function setCamera3(?Camera $camera3): self
    {
        $this->camera3 = $camera3;

        return $this;
    }

    public function getCamera4(): ?Camera
    {
        return $this->camera4;
    }

    public function setCamera4(?Camera $camera4): self
    {
        $this->camera4 = $camera4;

        return $this;
    }

    public function getSortIndex(): int
    {
        return $this->sortIndex;
    }

    public function setSortIndex(int $sortIndex): self
    {
        $this->sortIndex = $sortIndex;

        return $this;
    }

    public function getButtonStyle(): ButtonStyle
    {
        return $this->buttonStyle;
    }

    public function setButtonStyle(ButtonStyle $buttonStyle): self
    {
        $this->buttonStyle = $buttonStyle;

        return $this;
    }

    public function getIcon(): ButtonIcon
    {
        return $this->icon;
    }

    public function setIcon(ButtonIcon $icon): self
    {
        $this->icon = $icon;

        return $this;
    }
}

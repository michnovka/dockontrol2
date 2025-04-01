<?php

declare(strict_types=1);

namespace App\Entity;

use App\Controller\CP\Settings\BuildingController;
use App\Repository\BuildingRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Override;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: BuildingRepository::class)]
#[ORM\Table(name: 'buildings')]
class Building implements SearchableEntityInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\Column(length: 31, nullable: false)]
    #[Assert\Regex(
        pattern: '/^[A-Za-z0-9]+(?:\.[A-Za-z0-9]+)*$/'
    )]
    private string $name;

    #[ORM\ManyToOne(targetEntity: Group::class, fetch: 'LAZY', inversedBy: 'buildings')]
    private ?Group $defaultGroup = null;

    /**
     * @var Collection<int, Apartment> $apartments
     */
    #[ORM\OneToMany(targetEntity: Apartment::class, mappedBy: 'building', fetch: 'LAZY')]
    private Collection $apartments;

    /**
     * @var Collection<int, Permission>
     */
    #[ORM\ManyToMany(targetEntity: Permission::class, inversedBy: 'buildings')]
    #[ORM\JoinTable(
        name: 'building_permission',
        joinColumns: [new ORM\JoinColumn(name: 'building_id', referencedColumnName: 'id', onDelete: 'CASCADE')],
        inverseJoinColumns: [new ORM\JoinColumn(name: 'permission', referencedColumnName: 'name')],
    )]
    private Collection $permissions;

    /**
     * @var Collection<int, DockontrolNode>
     */
    #[ORM\OneToMany(targetEntity: DockontrolNode::class, mappedBy: 'building')]
    private Collection $dockontrolNodes;

    public function __construct()
    {
        $this->permissions = new ArrayCollection();
        $this->dockontrolNodes = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
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

    public function getDefaultGroup(): ?Group
    {
        return $this->defaultGroup;
    }

    public function setDefaultGroup(?Group $defaultGroup): static
    {
        $this->defaultGroup = $defaultGroup;

        return $this;
    }

    /**
     * @return Collection<int, Apartment>
     */
    public function getApartments(): Collection
    {
        return $this->apartments;
    }

    public function addApartment(Apartment $apartment): self
    {
        if (!$this->apartments->contains($apartment)) {
            $this->apartments->add($apartment);
        }

        return $this;
    }

    public function removeApartment(Apartment $apartment): self
    {
        if ($this->apartments->contains($apartment)) {
            $this->apartments->removeElement($apartment);
        }

        return $this;
    }

    /**
     * @return Collection<int, Permission>
     */
    public function getPermissions(): Collection
    {
        return $this->permissions;
    }

    public function addPermission(Permission $permission): static
    {
        if (!$this->permissions->contains($permission)) {
            $this->permissions->add($permission);
            $permission->addBuilding($this);
        }

        return $this;
    }

    public function removePermission(Permission $permission): static
    {
        if ($this->permissions->removeElement($permission)) {
            $permission->removeBuilding($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, DockontrolNode>
     */
    public function getDockontrolNodes(): Collection
    {
        return $this->dockontrolNodes;
    }

    public function addDockontrolNode(DockontrolNode $dockontrolNode): static
    {
        if (!$this->dockontrolNodes->contains($dockontrolNode)) {
            $this->dockontrolNodes->add($dockontrolNode);
            $dockontrolNode->setBuilding($this);
        }

        return $this;
    }

    public function removeDockontrolNode(DockontrolNode $dockontrolNode): static
    {
        if ($this->dockontrolNodes->contains($dockontrolNode)) {
            $this->dockontrolNodes->removeElement($dockontrolNode);
        }

        return $this;
    }

    #[Override]
    public function getTwigDisplayValue(): string
    {
        return $this->name;
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public static function getSearchAPIController(): string
    {
        return BuildingController::class;
    }
}

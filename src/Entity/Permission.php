<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\PermissionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PermissionRepository::class)]
#[ORM\Table(name: 'permissions')]
class Permission
{
    #[ORM\Id]
    #[ORM\Column(length: 63)]
    private string $name;

    #[ORM\Column(length: 63, options: ['default' => ''])]
    private string $namePretty;

    /**
     * @var Collection<int, Group>
     */
    #[ORM\ManyToMany(targetEntity: Group::class, mappedBy: 'permissions', fetch: 'LAZY')]
    private Collection $groups;

    /**
     * @var Collection<int, Building>
     */
    #[ORM\ManyToMany(targetEntity: Building::class, mappedBy: 'permissions', fetch: 'LAZY')]
    private Collection $buildings;

    /**
     * @var Collection<int, Camera>
     */
    #[ORM\OneToMany(targetEntity: Camera::class, mappedBy: 'permissionRequired', fetch: 'LAZY')]
    private Collection $cameras;

    public function __construct()
    {
        $this->groups = new ArrayCollection();
        $this->buildings = new ArrayCollection();
        $this->cameras = new ArrayCollection();
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

    public function getNamePretty(): string
    {
        return $this->namePretty;
    }

    public function setNamePretty(string $namePretty): self
    {
        $this->namePretty = $namePretty;

        return $this;
    }

    /**
     * @return Collection<int, Group>
     */
    public function getGroups(): Collection
    {
        return $this->groups;
    }

    public function addGroup(Group $group): static
    {
        if (!$this->groups->contains($group)) {
            $this->groups->add($group);
            $group->addPermissions($this);
        }

        return $this;
    }

    public function removeGroup(Group $group): static
    {
        if ($this->groups->removeElement($group)) {
            $group->removePermissions($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Building>
     */
    public function getBuildings(): Collection
    {
        return $this->buildings;
    }

    public function addBuilding(Building $building): static
    {
        if (!$this->buildings->contains($building)) {
            $this->buildings->add($building);
            $building->addPermission($this);
        }

        return $this;
    }

    public function removeBuilding(Building $building): static
    {
        if ($this->buildings->removeElement($building)) {
            $building->removePermission($this);
        }

        return $this;
    }

    public function getCameras(): Collection
    {
        return $this->cameras;
    }
}

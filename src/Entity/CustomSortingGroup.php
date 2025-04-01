<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\CustomSortingGroupRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CustomSortingGroupRepository::class)]
class CustomSortingGroup
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $name = null;

    #[ORM\Column(options: ['unsigned' => true])]
    #[Assert\Type('integer')]
    #[Assert\PositiveOrZero]
    private int $sortIndex;

    #[ORM\ManyToOne(inversedBy: 'customSortingGroups')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'cascade')]
    private User $user;

    #[ORM\Column(options: ['unsigned' => true])]
    #[Assert\Range(
        min: 1,
        max: 3
    )]
    private int $columnSize;

    /**
     * @var Collection<int, CustomSorting>
     */
    #[ORM\OneToMany(targetEntity: CustomSorting::class, mappedBy: 'customSortingGroup', fetch: 'EAGER', orphanRemoval: true)]
    #[ORM\JoinColumn(onDelete: 'cascade')]
    private Collection $customSortingElements;

    #[ORM\ManyToOne(targetEntity: CustomSorting::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'cascade')]
    private ?CustomSorting $isGroupForModal = null;

    public function __construct()
    {
        $this->customSortingElements = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getSortIndex(): int
    {
        return $this->sortIndex;
    }

    public function setSortIndex(int $sortIndex): static
    {
        $this->sortIndex = $sortIndex;

        return $this;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getColumnSize(): int
    {
        return $this->columnSize;
    }

    public function setColumnSize(int $columnSize): self
    {
        $this->columnSize = $columnSize;

        return $this;
    }

    public function getCustomSortingElements(): Collection
    {
        return $this->customSortingElements;
    }

    public function getIsGroupForModal(): ?CustomSorting
    {
        return $this->isGroupForModal;
    }

    public function setIsGroupForModal(?CustomSorting $isGroupForModal): self
    {
        $this->isGroupForModal = $isGroupForModal;

        return $this;
    }
}

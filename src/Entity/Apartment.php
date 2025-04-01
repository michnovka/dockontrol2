<?php

declare(strict_types=1);

namespace App\Entity;

use App\Controller\CP\Settings\ApartmentController;
use App\Repository\ApartmentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Override;

#[ORM\Entity(repositoryClass: ApartmentRepository::class)]
#[ORM\UniqueConstraint(
    name: 'apartment_name',
    columns: ['building_id', 'name']
)]
class Apartment implements SearchableEntityInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Building::class, fetch: 'EAGER', inversedBy: 'apartments')]
    #[ORM\JoinColumn(nullable: false)]
    private Building $building;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\ManyToOne]
    private ?Group $defaultGroup = null;

    /** @var Collection<int, User> $users*/
    #[ORM\OneToMany(targetEntity: User::class, mappedBy: 'apartment', fetch: 'LAZY')]
    private Collection $users;

    public function __construct()
    {
        $this->users = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getBuilding(): Building
    {
        return $this->building;
    }

    public function setBuilding(Building $building): static
    {
        $this->building = $building;

        return $this;
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

    public function getDefaultGroup(): ?Group
    {
        return $this->defaultGroup;
    }

    public function setDefaultGroup(?Group $defaultGroup): static
    {
        $this->defaultGroup = $defaultGroup;

        return $this;
    }

    #[Override]
    public function getTwigDisplayValue(): string
    {
        return $this->name . ' (' . $this->building->getName() . ') ';
    }

    /**
     * @inheritdoc
     */
    #[Override]
    public static function getSearchAPIController(): string
    {
        return ApartmentController::class;
    }

    /**
     * @return Collection<int, User>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }
}

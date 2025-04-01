<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\CarEnterDetailsRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: CarEnterDetailsRepository::class)]
#[ORM\Index(name: 'idx_user_order', columns: ['user_id', 'order'])]
#[ORM\Index(name: 'idx_building_order', columns: ['building_id', 'order'])]
class CarEnterDetails
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?Building $building = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'action', referencedColumnName: 'name', nullable: false)]
    private Action $action;

    #[ORM\Column(name: '`order`', nullable: false)]
    #[Assert\Positive]
    #[Assert\LessThanOrEqual(5, message: 'Can not add more then 5 car enter details.')]
    private int $order;

    #[ORM\Column(type: 'integer', nullable: false)]
    #[Assert\Range(
        notInRangeMessage: 'Wait seconds after enter must be between {{ min }} and {{ max }} seconds.',
        min: 0,
        max: 60
    )]
    private int $waitSecondsAfterEnter = 0;

    #[ORM\Column(type: 'integer', nullable: false)]
    #[Assert\Range(
        notInRangeMessage: 'Wait seconds after exit must be between {{ min }} and {{ max }} seconds.',
        min: 0,
        max: 60
    )]
    private int $waitSecondsAfterExit = 0;

    #[Assert\Callback]
    public function validate(ExecutionContextInterface $context): void
    {
        if (null === $this->user && null === $this->building || null !== $this->user && null !== $this->building) {
            $context
                ->buildViolation('Please select either user or building.')
                ->addViolation();
        }
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getBuilding(): ?Building
    {
        return $this->building;
    }

    public function setBuilding(?Building $building): static
    {
        $this->building = $building;

        return $this;
    }

    public function getAction(): Action
    {
        return $this->action;
    }

    public function setAction(Action $action): static
    {
        $this->action = $action;

        return $this;
    }

    public function getOrder(): int
    {
        return $this->order;
    }

    public function setOrder(int $order): static
    {
        $this->order = $order;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getWaitSecondsAfterEnter(): int
    {
        return $this->waitSecondsAfterEnter;
    }

    public function setWaitSecondsAfterEnter(int $waitSecondsAfterEnter): self
    {
        $this->waitSecondsAfterEnter = $waitSecondsAfterEnter;

        return $this;
    }

    public function getWaitSecondsAfterExit(): int
    {
        return $this->waitSecondsAfterExit;
    }

    public function setWaitSecondsAfterExit(int $waitSecondsAfterExit): self
    {
        $this->waitSecondsAfterExit = $waitSecondsAfterExit;

        return $this;
    }
}

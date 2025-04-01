<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Enum\ButtonIcon;
use App\Entity\Enum\ButtonStyle;
use App\Repository\CustomSortingRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: CustomSortingRepository::class)]
class CustomSorting
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\ManyToOne(fetch: 'EAGER')]
    #[ORM\JoinColumn(nullable: false)]
    private Button $button;

    #[ORM\Column(nullable: false, options: ['unsigned' => true])]
    #[Assert\Type('integer')]
    #[Assert\PositiveOrZero]
    private int $sortIndex;

    #[ORM\ManyToOne(targetEntity: CustomSortingGroup::class, inversedBy: "customSortingElements")]
    #[ORM\JoinColumn(nullable: false, onDelete: "cascade")]
    private CustomSortingGroup $customSortingGroup;

    #[ORM\Column(nullable: true, enumType: ButtonIcon::class)]
    private ?ButtonIcon $customButtonIcon = null;

    #[ORM\Column(nullable: true, enumType: ButtonStyle::class)]
    private ?ButtonStyle $customButtonStyle = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $customName = null;

    #[ORM\Column(nullable: false, options: ['default' => false])]
    private bool $allow1MinOpen = false;

    #[Assert\Callback]
    public function validateAllow1MinOpen(ExecutionContextInterface $context): void
    {
        if (!$this->button->isAllow1MinOpen() && $this->allow1MinOpen) {
            $context
                ->buildViolation('1-minute open is not allowed because the parent button has disabled it.')
                ->atPath('allow1MinOpen')
                ->addViolation();
        }
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getButton(): Button
    {
        return $this->button;
    }

    public function setButton(Button $button): static
    {
        $this->button = $button;

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

    public function getCustomSortingGroup(): CustomSortingGroup
    {
        return $this->customSortingGroup;
    }

    public function setCustomSortingGroup(CustomSortingGroup $customSortingGroup): static
    {
        $this->customSortingGroup = $customSortingGroup;

        return $this;
    }

    public function getCustomButtonIcon(): ?ButtonIcon
    {
        return $this->customButtonIcon;
    }

    public function setCustomButtonIcon(?ButtonIcon $customButtonIcon): static
    {
        $this->customButtonIcon = $customButtonIcon;

        return $this;
    }

    public function getCustomButtonStyle(): ?ButtonStyle
    {
        return $this->customButtonStyle;
    }

    public function setCustomButtonStyle(?ButtonStyle $customButtonStyle): static
    {
        $this->customButtonStyle = $customButtonStyle;

        return $this;
    }

    public function getCustomName(): ?string
    {
        return $this->customName;
    }

    public function setCustomName(?string $customName): static
    {
        $this->customName = $customName;

        return $this;
    }

    public function isAllow1MinOpen(): bool
    {
        return $this->allow1MinOpen;
    }

    public function setAllow1MinOpen(bool $allow1MinOpen): static
    {
        $this->allow1MinOpen = $allow1MinOpen;

        return $this;
    }
}

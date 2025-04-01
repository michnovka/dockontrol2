<?php

declare(strict_types=1);

namespace App\Entity\Log;

use App\Entity\User;
use App\Repository\Log\UserActionLogRepository;
use Carbon\CarbonImmutable;
use Doctrine\ORM\Mapping as ORM;
use Override;
use Symfony\Component\Serializer\Normalizer\NormalizableInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

#[ORM\Entity(repositoryClass: UserActionLogRepository::class)]
#[ORM\Index(columns: ['time'])]
class UserActionLog implements NormalizableInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'cascade')]
    private ?User $admin = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private CarbonImmutable $time;

    #[ORM\Column(type: 'text')]
    private string $description;

    public function __construct(?User $admin, string $description)
    {
        $this->admin = $admin;
        $this->description = $description;
        $this->time = CarbonImmutable::now();
    }
    public function getId(): int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->admin;
    }

    public function setUser(?User $admin): static
    {
        $this->admin = $admin;

        return $this;
    }

    public function getTime(): CarbonImmutable
    {
        return $this->time;
    }

    public function setTime(CarbonImmutable $time): static
    {
        $this->time = $time;

        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function normalize(NormalizerInterface $normalizer, ?string $format = null, array $context = []): array
    {
        return [
            'time' => $this->getTime()->unix(),
            'description' => $this->getDescription(),
            'admin' => $this->admin?->getId(),
        ];
    }
}

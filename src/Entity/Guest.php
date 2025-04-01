<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\GuestRepository;
use Carbon\CarbonImmutable;
use Doctrine\ORM\Mapping as ORM;
use Override;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: GuestRepository::class)]
#[ORM\UniqueConstraint(name: 'guests_hash_uindex', columns: ['hash'])]
#[ORM\Index('guests_users_id_fk', columns:['user_id'])]
#[ORM\Table(name: 'guests')]
class Guest implements UserInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $hash;

    #[ORM\ManyToOne(fetch: 'EAGER')]
    #[ORM\JoinColumn(nullable: false, onDelete: "cascade")]
    private User $user;

    #[ORM\Column]
    private CarbonImmutable $expires;

    #[ORM\Column(options: ['default' => -1, 'comment' => '-1 unlimited 0 no actions left N actions left'])]
    #[Assert\Range(min: -1, max: 100)]
    private int $remainingActions = -1;

    #[ORM\Column(nullable: false)]
    private CarbonImmutable $created;

    #[ORM\Column(nullable: false)]
    private bool $enabled = true;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $note = null;

    #[ORM\Column(nullable: true)]
    private ?CarbonImmutable $timeLastAction = null;

    #[ORM\Column(length: 50, nullable: false)]
    private string $defaultLanguage = 'cz';

    #[ORM\Column(nullable: true)]
    private ?CarbonImmutable $timeTosAccepted = null;

    public function __construct()
    {
        $this->hash = Uuid::v4();
        $this->created = CarbonImmutable::now();
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getHash(): Uuid
    {
        return $this->hash;
    }

    public function setHash(Uuid $hash): self
    {
        $this->hash = $hash;

        return $this;
    }

    public function getExpires(): CarbonImmutable
    {
        return $this->expires;
    }

    public function setExpires(CarbonImmutable $expires): self
    {
        $this->expires = $expires;

        return $this;
    }

    public function getRemainingActions(): int
    {
        return $this->remainingActions;
    }

    public function setRemainingActions(int $remainingActions): self
    {
        $this->remainingActions = $remainingActions;

        return $this;
    }

    public function isGuestPassValid(): bool
    {
        return ($this->expires < new CarbonImmutable() || $this->remainingActions == 0 || !$this->enabled);
    }

    /**
     * @return array<string>
     */
    #[Override]
    public function getRoles(): array
    {
        return ['ROLE_GUEST'];
    }

    #[Override]
    public function eraseCredentials(): void
    {
        // TODO: Implement eraseCredentials() method.
    }

    #[Override]
    public function getUserIdentifier(): string
    {
        /** @var non-empty-string $hash*/
        $hash = $this->hash->toString();

        return $hash;
    }

    public function getCreated(): CarbonImmutable
    {
        return $this->created;
    }

    public function setTimeCreated(CarbonImmutable $created): static
    {
        $this->created = $created;

        return $this;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): static
    {
        $this->enabled = $enabled;

        return $this;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(string $note): static
    {
        $this->note = $note;

        return $this;
    }

    public function getTimeLastAction(): ?CarbonImmutable
    {
        return $this->timeLastAction;
    }

    public function setTimeLastAction(?CarbonImmutable $timeLastAction): self
    {
        $this->timeLastAction = $timeLastAction;

        return $this;
    }

    public function getDefaultLanguage(): string
    {
        return $this->defaultLanguage;
    }

    public function setDefaultLanguage(string $defaultLanguage): void
    {
        $this->defaultLanguage = $defaultLanguage;
    }

    public function getTimeTosAccepted(): ?CarbonImmutable
    {
        return $this->timeTosAccepted;
    }

    public function setTimeTosAccepted(?CarbonImmutable $timeTosAccepted): self
    {
        $this->timeTosAccepted = $timeTosAccepted;

        return $this;
    }
}

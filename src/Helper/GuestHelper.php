<?php

declare(strict_types=1);

namespace App\Helper;

use App\Entity\Guest;
use App\Entity\User;
use App\Repository\GuestRepository;
use Carbon\CarbonImmutable;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

readonly class GuestHelper
{
    public function __construct(
        private GuestRepository $guestRepository,
        private EntityManagerInterface $entityManager,
        private ValidatorInterface $validator,
    ) {
    }

    public function getGuestByToken(string $token): ?Guest
    {
        $guest = $this->guestRepository->findOneBy(['hash' => $token]);

        if (!$guest instanceof Guest) {
            return null;
        }

        return $guest;
    }

    /**
     * @throws RuntimeException
     */
    public function createGuestPass(Guest $guest, int $intervalInHours): void
    {
        if ($intervalInHours < 1 || $intervalInHours > 186) {
            throw new RuntimeException('Interval must be between 1 and 186 hours.');
        }
        $guest->setExpires(CarbonImmutable::now()->addHours($intervalInHours));
        $this->entityManager->persist($guest);
        $this->entityManager->flush();
    }

    /**
     * @return Guest[]|null
     */
    public function getGuestPassForUser(User $user): ?array
    {
        return $this->guestRepository->getGuestPassForUser($user);
    }

    public function saveGuest(Guest $guest): void
    {
        $this->entityManager->persist($guest);
        $this->entityManager->flush();
    }

    public function deleteGuestPass(Guest $guest): void
    {
        $guest->setEnabled(false);
        $this->saveGuest($guest);
    }

    public function restoreGuestPass(Guest $guest): void
    {
        $guest->setEnabled(true);
        $this->saveGuest($guest);
    }
}

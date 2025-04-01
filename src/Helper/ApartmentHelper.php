<?php

declare(strict_types=1);

namespace App\Helper;

use App\Entity\Apartment;
use Doctrine\ORM\EntityManagerInterface;

readonly class ApartmentHelper
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function saveApartment(Apartment $apartment): void
    {
        $this->entityManager->persist($apartment);
        $this->entityManager->flush();
    }

    public function removeApartment(Apartment $apartment): void
    {
        $this->entityManager->remove($apartment);
        $this->entityManager->flush();
    }
}

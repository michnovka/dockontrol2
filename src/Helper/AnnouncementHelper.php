<?php

declare(strict_types=1);

namespace App\Helper;

use App\Entity\Announcement;
use App\Repository\AnnouncementRepository;
use App\Security\Voter\AnnouncementVoter;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Bundle\SecurityBundle\Security;
use Throwable;

readonly class AnnouncementHelper
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private Security $security,
        private AnnouncementRepository $announcementRepository,
    ) {
    }

    public function save(Announcement $announcement): void
    {
        $this->entityManager->persist($announcement);
        $this->entityManager->flush();
    }

    public function remove(Announcement $announcement): void
    {
        $this->entityManager->remove($announcement);
        $this->entityManager->flush();
    }

    /**
     * @param array<string> $announcementIds
     */
    public function bulkDelete(array $announcementIds): void
    {
        $this->entityManager->beginTransaction();
        try {
            foreach ($announcementIds as $announcementId) {
                $announcement = $this->announcementRepository->find($announcementId);

                if (!$announcement instanceof Announcement) {
                    throw new RuntimeException('Announcement not found.');
                }

                if (!$this->security->isGranted(AnnouncementVoter::DELETE, $announcement)) {
                    throw new RuntimeException('You don\'t have permission to delete announcement.');
                }

                $this->entityManager->remove($announcement);
            }
            $this->entityManager->flush();
            $this->entityManager->commit();
        } catch (Throwable $throwable) {
            $this->entityManager->rollback();
            throw new RuntimeException('Failed to delete announcements, ' . $throwable->getMessage());
        }
    }
}

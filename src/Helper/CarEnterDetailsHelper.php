<?php

declare(strict_types=1);

namespace App\Helper;

use App\Entity\Action;
use App\Entity\Apartment;
use App\Entity\Building;
use App\Entity\CarEnterDetails;
use App\Entity\Guest;
use App\Entity\User;
use App\Repository\CarEnterDetailsRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use RuntimeException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Throwable;

readonly class CarEnterDetailsHelper
{
    public function __construct(
        private CarEnterDetailsRepository $carEnterDetailsRepository,
        private EntityManagerInterface $entityManager,
        private ValidatorInterface $validator,
    ) {
    }

    /**
     * @param array<array-key, array{id: int, order: int, new_order: int}> $orders
     */
    public function changeCarDetailsOrder(array $orders): void
    {
        foreach ($orders as $order) {
            if (!empty($order) && array_key_exists('id', $order) && array_key_exists('new_order', $order)) {
                $orderId = $order['id'];
                $newOrder = $order['new_order'];
                $carEnterDetails = $this->carEnterDetailsRepository->find($orderId);
                if ($carEnterDetails instanceof CarEnterDetails) {
                    $carEnterDetails->setOrder($newOrder);
                    $this->entityManager->persist($carEnterDetails);
                }
            }
        }

        $this->entityManager->flush();
    }

    /**
     * @throws Exception
     */
    public function validateCarEnterDetails(CarEnterDetails $carEnterDetails): void
    {
        $violations = $this->validator->validate($carEnterDetails);

        if ($violations->count() > 0) {
            /** @var string $violationMessage*/
            $violationMessage = $violations[0]?->getMessage();
            throw new RuntimeException($violationMessage);
        }
    }

    public function removeCarEnterDetail(CarEnterDetails $carEnterDetails): void
    {
        $this->entityManager->remove($carEnterDetails);
        $this->entityManager->flush();
    }

    /**
     * @throws Exception
     */
    public function saveCarEnterDetails(
        Action $action,
        int $waitSecondsAfterEnter,
        int $waitSecondsAfterExit,
        ?User $user = null,
        ?Building $building = null,
    ): void {
        if (!$user && !$building) {
            throw new RuntimeException('Please select either user or building.');
        }

        $this->entityManager->beginTransaction();

        try {
            $carEnterDetails = new CarEnterDetails();
            $currentLastOrder = 0;

            if ($user instanceof User) {
                $this->entityManager->lock($user, LockMode::PESSIMISTIC_WRITE);
                $carEnterDetails->setUser($user);
                $currentLastOrder = $this->carEnterDetailsRepository->getMaxOrderNumber($user);
            }

            if ($building instanceof Building) {
                $this->entityManager->lock($building, LockMode::PESSIMISTIC_WRITE);
                $carEnterDetails->setBuilding($building);
                $currentLastOrder = $this->carEnterDetailsRepository->getMaxOrderNumber($building);
            }

            $carEnterDetails->setAction($action);
            $carEnterDetails->setOrder($currentLastOrder + 1);
            $carEnterDetails->setWaitSecondsAfterEnter($waitSecondsAfterEnter);
            $carEnterDetails->setWaitSecondsAfterExit($waitSecondsAfterExit);

            $this->validateCarEnterDetails($carEnterDetails);


            $this->entityManager->persist($carEnterDetails);
            $this->entityManager->flush();
            $this->entityManager->commit();
        } catch (Throwable $throwable) {
            $this->entityManager->rollback();
            throw new RuntimeException('Failed to save car enter details., ' . $throwable->getMessage(), 0, $throwable);
        }
    }

    /**
     * @return array<CarEnterDetails>
     */
    public function getCarEnterDetailsForUser(User|Guest $user, bool $isExit = false): array
    {
        $guest = null;

        if ($user instanceof Guest) {
            $guest = $user;
            $user = $guest->getUser();
        }

        $usersCarEnterDetails = $this->carEnterDetailsRepository->getUsersCarEnterDetails($user, $isExit);
        if (!empty($usersCarEnterDetails)) {
            return $usersCarEnterDetails;
        }

        $userApartment = $user->getApartment();
        if (!$userApartment instanceof Apartment) {
            throw new RuntimeException('User does not belongs to any apartment.');
        }

        $userBuildingsCarEnterDetails = $this->carEnterDetailsRepository->getBuildingsCarEnterDetails($userApartment->getBuilding(), $isExit);

        if (empty($userBuildingsCarEnterDetails)) {
            throw new RuntimeException('Car enter details not found for user.');
        }

        return $userBuildingsCarEnterDetails;
    }
}

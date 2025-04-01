<?php

declare(strict_types=1);

namespace App\Helper;

use App\Entity\Button;
use App\Entity\Enum\ButtonType;
use App\Entity\Enum\SpecialButtonType;
use App\Entity\Guest;
use App\Entity\User;
use App\Helper\CarEnterDetailsHelper;
use App\Repository\ButtonRepository;
use Carbon\CarbonImmutable;
use Doctrine\ORM\EntityManagerInterface;

readonly class ButtonHelper
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ButtonRepository $buttonRepository,
        private ActionHelper $actionHelper,
        private CarEnterDetailsHelper $carEnterDetailsHelper,
    ) {
    }

    public function saveButton(Button $button, bool $createAction = false): void
    {
        $this->entityManager->persist($button);
        $this->entityManager->flush();
    }

    public function removeButton(Button $button): void
    {
        $this->entityManager->remove($button);
        $this->entityManager->flush();
    }

    /**
     * @return array{
     *      buttons: array{
     *          carEnterExitButtons: Button[],
     *          gateButtons: Button[],
     *          entranceButtons: Button[],
     *          elevatorButtons: Button[],
     *          multiButtons: Button[],
     *          customButtons: Button[],
     *      },
     *      nameConflicts: array<string, int>,
     *     userButtons: Button[]
     *  }
     */
    public function getUserButtonsSeparatedByTypes(User $user, bool $fullView = false): array
    {
        $userButtons = $this->buttonRepository->getUserButtons($user, fullView: $fullView);

        $buttonsSeparatedByTypes = [
            'carEnterExitButtons' => [],
            'gateButtons' => [],
            'entranceButtons' => [],
            'elevatorButtons' => [],
            'multiButtons' => [],
            'customButtons' => [],
        ];

        $buttonNameConflicts = [];

        foreach ($userButtons as $button) {
            $buttonType = match ($button->getType()) {
                ButtonType::GATE => 'gateButtons',
                ButtonType::ENTRANCE => 'entranceButtons',
                ButtonType::ELEVATOR => 'elevatorButtons',
                ButtonType::MULTI => 'multiButtons',
                ButtonType::CUSTOM => 'customButtons',
            };
            $buttonsSeparatedByTypes[$buttonType][] = $button;

            if (array_key_exists($button->getName(), $buttonNameConflicts)) {
                $buttonNameConflicts[$button->getName()]++;
            } else {
                $buttonNameConflicts[$button->getName()] = 1;
            }
        }

        return ['buttons' => $buttonsSeparatedByTypes, 'nameConflicts' => $buttonNameConflicts, 'userButtons' => $userButtons];
    }

    public function getTotalUserButtonCount(User $user): int
    {
        return $this->buttonRepository->getTotalUserButtonCount($user);
    }

    public function processButtonClick(
        Button|SpecialButtonType $button,
        User|Guest $initiator,
        bool $isOneMinute = false,
    ): void {
        if ($button instanceof Button) {
            $action = $button->getAction();
            $startTime = CarbonImmutable::now();
            $this->actionHelper->addActionToQueue($action, $initiator, null);

            if ($isOneMinute) {
                for ($i = 5; $i < 60; $i += 5) {
                    $startTime = $startTime->addSeconds(5);
                    $this->actionHelper->addActionToQueue($action, $initiator, $startTime, false);
                }
            }
        } else {
            $carEnterExitDetails = $this->carEnterDetailsHelper->getCarEnterDetailsForUser($initiator, $button !== SpecialButtonType::CAR_ENTER);
            $startTime = null;
            foreach ($carEnterExitDetails as $carEnterDetails) {
                $action = $carEnterDetails->getAction();
                $this->actionHelper->addActionToQueue($action, $initiator, $startTime);
                if (empty($startTime)) {
                    $startTime = CarbonImmutable::now();
                }
                $startTime = $startTime->addSeconds($button === SpecialButtonType::CAR_ENTER ? $carEnterDetails->getWaitSecondsAfterEnter() : $carEnterDetails->getWaitSecondsAfterExit());
            }
        }
    }
}

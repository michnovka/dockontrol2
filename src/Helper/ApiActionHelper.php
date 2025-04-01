<?php

declare(strict_types=1);

namespace App\Helper;

use App\Entity\Button;
use App\Entity\Camera;
use App\Entity\DockontrolNode;
use App\Entity\Log\ApiCallFailedLog\AbstractApiCallFailedLog;
use App\Entity\Log\ApiCallLog\AbstractApiCallLog;
use App\Entity\Log\ApiCallLog\API2CallLog;
use App\Entity\Log\ApiCallLog\DockontrolNodeAPICallLog;
use App\Entity\Log\ApiCallLog\LegacyAPICallLog;
use App\Entity\User;
use App\Repository\NukiRepository;
use Carbon\CarbonImmutable;
use Doctrine\ORM\EntityManagerInterface;

readonly class ApiActionHelper
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ButtonHelper $buttonHelper,
        private NukiRepository $nukiRepository,
    ) {
    }

    public function logLegacyAPICall(User $user, string $action, string $ip): void
    {
        $apiCallLog = new LegacyAPICallLog();
        $apiCallLog->setUser($user);
        $apiCallLog->setApiAction($action);
        $this->logAPICall($apiCallLog, $action, $ip);
    }

    public function logAPI2Call(string $apiKey, User $user, string $action, string $ip): void
    {
        $apiCallLog = new API2CallLog();
        $apiCallLog->setApiKey($apiKey);
        $apiCallLog->setUser($user);
        $apiCallLog->setIp($ip);
        $this->logAPICall($apiCallLog, $action, $ip);
    }

    public function logDockontrolNodeAPICall(DockontrolNode $dockontrolNode, string $action, string $ip): void
    {
        $apiCallLog = new DockontrolNodeAPICallLog();
        $apiCallLog->setDockontrolNode($dockontrolNode);
        $this->logAPICall($apiCallLog, $action, $ip);
    }

    public function logAPICallFailed(
        AbstractApiCallFailedLog $apiCallFailedLog,
        string $ip,
        string $apiEndpoint,
        string $reason,
    ): void {
        $apiCallFailedLog->setReason($reason);
        $apiCallFailedLog->setApiEndpoint($apiEndpoint);
        $apiCallFailedLog->setIp($ip);
        $apiCallFailedLog->setTime(CarbonImmutable::now());
        $this->entityManager->persist($apiCallFailedLog);
        $this->entityManager->flush();
    }

    /**
     * @return array<array-key, array<string, mixed>>
     */
    public function getLegacyAPIAllowedActionsForUser(User $user): array
    {

        $reply = [];

        if ($user->isCarEnterExitAllowed()) {
            $reply[] = [
                'id' => -2,
                'action' => 'enter',
                'type' => 'carenter',
                'name' => 'Car Enter',
                'has_camera' => false,
                'allow_widget' => true,
                'allow_1min_open' => false,
                'icon' => 'enter',
            ];

            $reply[] = [
                'id' => -1,
                'action' => 'exit',
                'type' => 'carexit',
                'name' => 'Car Exit',
                'has_camera' => false,
                'allow_widget' => true,
                'allow_1min_open' => false,
                'icon' => 'exit',
            ];
        }

        $buttonsSeperatedByTypes = $this->buttonHelper->getUserButtonsSeparatedByTypes($user);

        $buttons = $buttonsSeperatedByTypes['userButtons'];
        $nameConflicts = $buttonsSeperatedByTypes['nameConflicts'];

        /** @var Button $button*/
        foreach ($buttons as $button) {
            $row = [
                'id' => $button->getId(),
                // This used to be action name. However this is not secure, since actions have no related permissions. So we need to break compatibility here
                'action' => $button->getId(),
                'type' => $button->getType()->getReadable(),
                'name' => $button->getName() . ($nameConflicts[$button->getName()] > 1 ? ' ' . $button->getNameSpecification() : ''),
                'has_camera' => $user->getHasCameraAccess() && $button->getCamera1() instanceof Camera,
                'allow_widget' => true,
                'allow_1min_open' => $button->isAllow1MinOpen(),
                'icon' => $button->getIcon()->getReadable(),
            ];

            if ($row['has_camera']) {
                $row['cameras'] = [];
                $camera1 = $button->getCamera1();
                $camera2 = $button->getCamera2();
                $camera3 = $button->getCamera3();
                $camera4 = $button->getCamera4();

                $row['cameras'][] = $camera1 instanceof Camera ? $camera1->getNameId() : null;

                if ($camera2 instanceof Camera) {
                    $row['cameras'][] = $camera2->getNameId();
                }

                if ($camera3 instanceof Camera) {
                    $row['cameras'][] = $camera3->getNameId();
                }

                if ($camera4 instanceof Camera) {
                    $row['cameras'][] = $camera4->getNameId();
                }
            }

            $reply[] = $row;
        }

        $nukis = $this->nukiRepository->findBy(['user' => $user]);

        if (!empty($nukis)) {
            foreach ($nukis as $nuki) {
                $reply[] = [
                    'id' => $nuki->getId(),
                    'action' => null,
                    'type' => 'nuki',
                    'name' => $nuki->getName(),
                    'can_lock' => $nuki->isCanLock(),
                    'has_camera' => false,
                    'allow_widget' => false,
                    'icon' => 'nuki',
                    'nuki_pin_required' => !empty($nuki->getPin()),
                ];
            }
        }

        return $reply;
    }

    private function logAPICall(AbstractApiCallLog $apiCallLog, string $action, string $ip): void
    {
        $apiCallLog->setIp($ip);
        $apiCallLog->setTime(new CarbonImmutable());
        $apiCallLog->setApiAction($action);
        $this->entityManager->persist($apiCallLog);
        $this->entityManager->flush();
    }
}

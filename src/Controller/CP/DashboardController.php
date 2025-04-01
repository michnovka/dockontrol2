<?php

declare(strict_types=1);

namespace App\Controller\CP;

use App\Entity\Enum\ConfigName;
use App\Entity\Enum\UserRole;
use App\Entity\User;
use App\Helper\ConfigHelper;
use App\Helper\CronHelper;
use App\Repository\ActionQueueCronGroupRepository;
use App\Repository\ActionQueueRepository;
use App\Repository\AnnouncementRepository;
use App\Repository\DockontrolNodeRepository;
use App\Repository\UserDeletionRequestRepository;
use App\Repository\UserRepository;
use App\Security\Expression\RoleRequired;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted(new RoleRequired(UserRole::ADMIN))]
class DashboardController extends AbstractCPController
{
    public function __construct(
        private readonly ActionQueueRepository $actionQueueRepository,
        private readonly UserRepository $userRepository,
        private readonly ActionQueueCronGroupRepository $cronGroupRepository,
        private readonly DockontrolNodeRepository $dockontrolNodeRepository,
        private readonly CronHelper $cronHelper,
        #[Autowire('%kernel.project_dir%')]
        private string $projectDir,
        private readonly ConfigHelper $configHelper,
        private readonly UserDeletionRequestRepository $userDeletionRequestRepository,
        private readonly AnnouncementRepository $announcementRepository,
    ) {
    }

    #[Route('/dashboard', name: 'cp_dashboard')]
    public function index(): Response
    {
        /** @var User $user*/
        $user = $this->getUser();

        $lastFiveActions = $this->actionQueueRepository->getLastFiveActionQueues();
        $lastFiveUsers = $this->userRepository->getLastFiveUsersForAdmin($user);
        $lastFiveUserDeletionRequests = $this->userDeletionRequestRepository->getLastFiveUserDeletionRequest();

        $cronHealthStatus = $this->cronHelper->getCronHealthStatus();
        $lastExecuteActionQueueCron = $cronHealthStatus['ACTION_QUEUE'];
        $lastExecuteMonitorCron = $cronHealthStatus['MONITOR'];
        $lastExecuteDBCleanupCron = $cronHealthStatus['DB_CLEANUP'];
        $cronGroups = $this->cronGroupRepository->findAll();

        $checkActiveNodes = $this->dockontrolNodeRepository->getActiveNodesCount();
        $checkAllNodesAreOnline = $checkActiveNodes['allNodesAreOnline'];
        $allNodesCount = $checkActiveNodes['totalNodes'];
        $onlineNodesCount = $checkActiveNodes['onlineNodes'];
        $nodesWhichAreNotActive = null;

        if (!$checkAllNodesAreOnline) {
            $nodesWhichAreNotActive = $this->dockontrolNodeRepository->getNodesWhichAreNotOnline();
        }
        $hasNodesWhichAreNotOnline = !$checkAllNodesAreOnline;
        $hasExpiredOrPlannedAnnouncements = false;
        $hasAdminBeenNotified = $this->configHelper->getConfigValue(ConfigName::DOCKONTROL_NODE_ISSUE_ADMIN_NOTIFIED);
        $allAnnouncements = $this->announcementRepository->getAnnouncementsForAdmin($user);
        $announcements = $this->announcementRepository->getAnnouncementsForAdmin($user, true);
        if (!empty($allAnnouncements) && !empty($announcements)) {
            $hasExpiredOrPlannedAnnouncements = count($announcements) !== count($allAnnouncements);
        }

        return $this->render('cp/dashboard/index.html.twig', [
            'lastFiveActions' => $lastFiveActions,
            'lastFiveUsers' => $lastFiveUsers,
            'lastExecuteActionQueueCron' => $lastExecuteActionQueueCron,
            'lastExecuteMonitorCron' => $lastExecuteMonitorCron,
            'lastExecuteDBCleanupCron' => $lastExecuteDBCleanupCron,
            'projectDir' => $this->projectDir,
            'cronGroups' => $cronGroups,
            'allNodesAreOnline' => $checkAllNodesAreOnline,
            'allNodesCount' => $allNodesCount,
            'onlineNodesCount' => $onlineNodesCount,
            'nodesWhichAreNotActive' => $nodesWhichAreNotActive,
            'hasAdminBeenNotified' => $hasAdminBeenNotified,
            'hasNodesWhichAreNotOnline' => $hasNodesWhichAreNotOnline,
            'announcements' => $announcements,
            'lastFiveUserDeletionRequests' => $lastFiveUserDeletionRequests,
            'hasExpiredOrPlannedAnnouncements' => $hasExpiredOrPlannedAnnouncements,
        ]);
    }
}

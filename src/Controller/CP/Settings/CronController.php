<?php

declare(strict_types=1);

namespace App\Controller\CP\Settings;

use App\Controller\CP\AbstractCPController;
use App\Helper\CronHelper;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/settings/cron')]
#[IsGranted('ROLE_SUPER_ADMIN')]
class CronController extends AbstractCPController
{
    public function __construct(
        private readonly CronHelper $cronHelper,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {
    }

    #[Route('/', name: 'cp_settings_cron')]
    public function index(): Response
    {
        $cronHealthStatus = $this->cronHelper->getCronHealthStatus();
        $lastExecuteActionQueueCron = $cronHealthStatus['ACTION_QUEUE'];
        $lastExecuteMonitorCron = $cronHealthStatus['MONITOR'];
        $lastExecuteDBCleanupCron = $cronHealthStatus['DB_CLEANUP'];

        return $this->render('cp/settings/cron/index.html.twig', [
            'lastExecuteActionQueueCron' => $lastExecuteActionQueueCron,
            'lastExecuteMonitorCron' => $lastExecuteMonitorCron,
            'lastExecuteDBCleanupCron' => $lastExecuteDBCleanupCron,
            'projectDir' => $this->projectDir,
        ]);
    }
}

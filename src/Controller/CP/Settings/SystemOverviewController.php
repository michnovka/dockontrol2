<?php

declare(strict_types=1);

namespace App\Controller\CP\Settings;

use App\Controller\CP\AbstractCPController;
use App\Helper\SystemOverviewHelper;
use Doctrine\DBAL\Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_SUPER_ADMIN')]
class SystemOverviewController extends AbstractCPController
{
    public function __construct(private readonly SystemOverviewHelper $systemOverviewHelper)
    {
    }

    /**
     * @throws Exception
     */
    #[Route('/system-overview', name: 'cp_settings_system_overview')]
    public function index(Request $request): Response
    {
        $phpInfo = $this->systemOverviewHelper->getPHPInfo();
        /** @var string $dbURL */
        $dbURL = $request->server->get('DATABASE_URL');
        $dbInfo = $this->systemOverviewHelper->getDBInfo($dbURL);
        $osInfo = $this->systemOverviewHelper->getOSInfo();
        $diskUsage = $this->systemOverviewHelper->getDiskUsage();
        $redisInfo = $this->systemOverviewHelper->getRedisInfo();
        $meilisearchInfo = $this->systemOverviewHelper->getMeilisearchInfo();
        $usingDefaultAppSecret = $this->systemOverviewHelper->usingDefaultAppSecret();

        return $this->render('cp/settings/system_overview/index.html.twig', [
            'phpInfo' => $phpInfo,
            'dbInfo' => $dbInfo,
            'osInfo' => $osInfo,
            'diskUsage' => $diskUsage,
            'redisInfo' => $redisInfo,
            'meilisearchInfo' => $meilisearchInfo,
            'usingDefaultAppSecret' => $usingDefaultAppSecret,
        ]);
    }
}

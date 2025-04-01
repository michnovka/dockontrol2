<?php

declare(strict_types=1);

namespace App\Controller\CP\Stats;

use App\Controller\CP\AbstractCPController;
use App\Entity\Enum\UserRole;
use App\Helper\StatsHelper;
use App\Security\Expression\RoleRequired;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/stats/usage')]
#[IsGranted(new RoleRequired(UserRole::ADMIN))]
class StatsController extends AbstractCPController
{
    public function __construct(private readonly StatsHelper $statsHelper)
    {
    }

    #[Route('/', name: 'cp_stats_usage')]
    public function index(): Response
    {
        $stats = $this->statsHelper->getStats();
        return $this->render('cp/stats/usage/index.html.twig', [
            'stats' => $stats['stats'],
            'periods' => $stats['periods'],
        ]);
    }
}

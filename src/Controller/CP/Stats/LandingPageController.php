<?php

declare(strict_types=1);

namespace App\Controller\CP\Stats;

use App\Controller\CP\AbstractCPController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class LandingPageController extends AbstractCPController
{
    #[Route('/stats', name: 'cp_stats')]
    #[IsGranted('ROLE_ADMIN')]
    public function index(): Response
    {
        return $this->render('cp/stats/landing_page/index.html.twig');
    }
}

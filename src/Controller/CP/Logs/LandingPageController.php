<?php

declare(strict_types=1);

namespace App\Controller\CP\Logs;

use App\Controller\CP\AbstractCPController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class LandingPageController extends AbstractCPController
{
    #[Route('/logs', name: 'cp_logs')]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function index(): Response
    {
        return $this->render('cp/logs/landing_page/index.html.twig');
    }
}

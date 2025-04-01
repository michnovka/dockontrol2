<?php

declare(strict_types=1);

namespace App\Controller\CP\Settings;

use App\Controller\CP\AbstractCPController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class LandingPageController extends AbstractCPController
{
    #[Route('/settings', name: 'cp_settings')]
    #[IsGranted('ROLE_ADMIN')]
    public function index(): Response
    {
        return $this->render('cp/settings/landing_page/index.html.twig');
    }
}

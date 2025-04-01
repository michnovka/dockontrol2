<?php

declare(strict_types=1);

namespace App\Controller\CP\AccessManagement;

use App\Controller\CP\AbstractCPController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class LandingPageController extends AbstractCPController
{
    #[Route('/access-management', name: 'cp_access_management')]
    #[IsGranted('ROLE_ADMIN')]
    public function index(): Response
    {
        return $this->render('cp/access_management/landing_page/index.html.twig');
    }
}

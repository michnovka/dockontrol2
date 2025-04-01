<?php

declare(strict_types=1);

namespace App\Controller\CP;

use App\Controller\CP\AbstractCPController;
use App\Entity\Enum\UserRole;
use App\Security\Expression\RoleRequired;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class MainController extends AbstractCPController
{
    #[Route('/', name: 'cp_main')]
    #[IsGranted(new RoleRequired(UserRole::ADMIN))]
    public function index(): Response
    {
        return $this->redirectToRoute('cp_dashboard');
    }
}

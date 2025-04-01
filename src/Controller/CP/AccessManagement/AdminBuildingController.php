<?php

declare(strict_types=1);

namespace App\Controller\CP\AccessManagement;

use App\Controller\CP\AbstractCPController;
use App\Entity\Enum\UserRole;
use App\Entity\User;
use App\Form\AdminBuildingType;
use App\Helper\UserActionLogHelper;
use App\Helper\UserHelper;
use App\Repository\UserRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;

#[Route('/access-management/admin-building')]
#[IsGranted('ROLE_SUPER_ADMIN')]
class AdminBuildingController extends AbstractCPController
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UserHelper $userHelper,
        private readonly UserActionLogHelper $userActionLogHelper,
    ) {
    }

    #[Route('/', name: 'cp_access_management_admin_building')]
    public function index(Request $request, PaginatorInterface $paginator): Response
    {
        /** @var User $adminUser */
        $adminUser = $this->getUser();
        $numberOfRecords = $request->query->getInt('numberOfRecords', 25);

        $queryBuilder = $this->userRepository->getQueryBuilder($adminUser, getOnlyAdminUsers: true);
        $adminUsers = $paginator->paginate($queryBuilder, $request->query->getInt('page', 1), $numberOfRecords, [
            'defaultSortFieldName' => 'u.id',
            'defaultSortDirection' => 'ASC',
        ]);

        return $this->render('cp/access_management/admin_building/index.html.twig', [
            'adminUsers' => $adminUsers,
            'numberOfRecords' => $numberOfRecords,
        ]);
    }

    #[Route('/{id}/edit', name: 'cp_access_management_admin_building_edit')]
    public function edit(Request $request, #[MapEntity(id: 'id')] User $user): Response
    {
        /** @var User $adminUser */
        $adminUser = $this->getUser();
        if ($user->getRole() !== UserRole::ADMIN) {
            $this->addFlash('danger', 'Cannot assign buildings to non-admin-user.');
            return $this->redirectToRoute('cp_access_management_admin_building');
        }

        $form = $this->createForm(AdminBuildingType::class, $user, [
            'buildings' => $user->getAdminBuildings()->getValues(),
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->userHelper->saveUser($user);
                $this->userActionLogHelper->addUserActionLog('Updated admin buildings for user #' . $user->getId() . ' (' . $user->getEmail() . ')', $adminUser);
                $this->addFlash('success', 'Admin buildings updated successfully.');
            } catch (Throwable $throwable) {
                $this->addFlash('danger', 'Failed to update admin buildings ' . $throwable->getMessage());
            }

            return  $this->redirectToRoute('cp_access_management_admin_building');
        }

        return $this->render('cp/access_management/admin_building/edit.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }
}

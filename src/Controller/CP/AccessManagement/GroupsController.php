<?php

declare(strict_types=1);

namespace App\Controller\CP\AccessManagement;

use App\Controller\CP\AbstractCPController;
use App\Entity\Group;
use App\Entity\User;
use App\Form\GroupType;
use App\Helper\GroupHelper;
use App\Helper\UserActionLogHelper;
use App\Repository\GroupRepository;
use App\Security\Voter\GroupVoter;
use Knp\Component\Pager\PaginatorInterface;
use RuntimeException;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;

#[Route('/access-management/groups')]
class GroupsController extends AbstractCPController
{
    public function __construct(
        private readonly GroupRepository $groupRepository,
        private readonly GroupHelper $groupHelper,
        private readonly UserActionLogHelper $userActionLogHelper,
    ) {
    }

    #[Route('/', name: 'cp_access_management_groups')]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function index(Request $request, PaginatorInterface $paginator): Response
    {
        $numberOfRecords = $request->query->getInt('numberOfRecords', 25);

        $queryBuilder = $this->groupRepository->getQueryBuilder();
        $groups = $paginator->paginate($queryBuilder, $request->query->getInt('page', 1), $numberOfRecords);

        return $this->render('cp/access_management/group/index.html.twig', [
            'groups' => $groups,
            'numberOfRecords' => $numberOfRecords,
        ]);
    }

    #[Route('/new', name: 'cp_access_management_group_new')]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function new(Request $request): Response
    {
        /** @var User $adminUser*/
        $adminUser = $this->getUser();
        $group = new Group();
        $form = $this->createForm(GroupType::class, $group);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                if (!$this->isGranted(GroupVoter::CREATE, $group)) {
                    throw new RuntimeException('You don\'t have permission to create group.');
                }

                $this->groupHelper->saveGroup($group);
                $this->userActionLogHelper->addUserActionLog('Created group #' . $group->getId() . ' (' . $group->getName() . ') ', $adminUser);
                $this->addFlash('success', 'Group created successfully.');
            } catch (Throwable $throwable) {
                $this->addFlash('danger', 'Failed to create group ' . $throwable->getMessage());
            }

            return $this->redirectToRoute('cp_access_management_groups');
        }

        return $this->render('cp/access_management/group/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'cp_access_management_group_edit')]
    #[IsGranted(GroupVoter::EDIT, 'group')]
    public function edit(Request $request, #[MapEntity(id: 'id')] Group $group): Response
    {
        /** @var User $adminUser*/
        $adminUser = $this->getUser();
        $form = $this->createForm(GroupType::class, $group);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                if (!$this->isGranted(GroupVoter::EDIT, $group)) {
                    throw new RuntimeException('You don\'t have permission to edit group.');
                }

                $this->groupHelper->saveGroup($group);
                $this->userActionLogHelper->addUserActionLog('Updated group #' . $group->getId() . ' (' . $group->getName() . ') ', $adminUser);
                $this->addFlash('success', 'Group updated successfully.');
            } catch (Throwable $throwable) {
                $this->addFlash('danger', 'Failed to update group ' . $throwable->getMessage());
            }

            return $this->redirectToRoute('cp_access_management_groups');
        }

        return $this->render('cp/access_management/group/edit.html.twig', [
            'form' => $form->createView(),
            'group' => $group,
        ]);
    }

    #[Route('/{id}/delete', name: 'cp_access_management_group_delete')]
    #[IsGranted(GroupVoter::DELETE, 'group')]
    public function delete(Request $request, #[MapEntity(id: 'id')] Group $group): JsonResponse
    {
        /** @var User $adminUser*/
        $adminUser = $this->getUser();
        $csrfToken = (string) $request->getPayload()->get('_csrf');
        $errorMessage = null;
        $status = false;
        if (!$this->isCsrfTokenValid('groupcsrf', $csrfToken)) {
            $errorMessage = 'Invalid CSRF token.';
        } else {
            try {
                $this->userActionLogHelper->addUserActionLog('Deleted group #' . $group->getId() . ' (' . $group->getName() . ') ', $adminUser, false);
                $this->groupHelper->deleteGroup($group);
                $status = true;
                $this->addFlash('danger', 'Group deleted successfully.');
            } catch (Throwable $throwable) {
                $errorMessage = 'Failed to delete group ' . $throwable->getMessage();
            }
        }
        return $this->json(['status' => $status, 'errorMessage' => $errorMessage]);
    }
}

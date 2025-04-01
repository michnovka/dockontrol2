<?php

declare(strict_types=1);

namespace App\Controller\CP\Settings;

use App\Controller\CP\AbstractCPController;
use App\Entity\Action;
use App\Entity\ActionBackupDockontrolNode;
use App\Entity\User;
use App\Form\ActionBackupDockontrolNodeType;
use App\Form\ActionType;
use App\Helper\ActionBackupDockontrolNodeHelper;
use App\Helper\ActionHelper;
use App\Helper\UserActionLogHelper;
use App\Repository\ActionRepository;
use App\Security\Voter\ActionVoter;
use Knp\Component\Pager\PaginatorInterface;
use RuntimeException;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;

#[Route('/settings/action')]
class ActionController extends AbstractCPController
{
    public function __construct(
        private readonly ActionRepository $actionRepository,
        private readonly ActionHelper $actionHelper,
        private readonly UserActionLogHelper $userActionLogHelper,
        private readonly ActionBackupDockontrolNodeHelper $actionBackupDockontrolNodeHelper,
    ) {
    }

    #[Route('/', name: 'cp_settings_action')]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function index(Request $request, PaginatorInterface $paginator): Response
    {
        $numberOfRecords = $request->query->getInt('numberOfRecords', 25);
        $cronGroupName = $request->query->getString('cron_group');

        $queryBuilder = $this->actionRepository->getQueryBuilder($cronGroupName, true);
        $dockontrolActions = $paginator->paginate($queryBuilder, $request->query->getInt('page', 1), $numberOfRecords);

        return $this->render('cp/settings/action/index.html.twig', [
            'dockontrolActions' => $dockontrolActions,
            'numberOfRecords' => $numberOfRecords,
        ]);
    }

    #[Route('/new', name: 'cp_settings_action_new')]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function new(Request $request): Response
    {
        /** @var User $adminUser*/
        $adminUser = $this->getUser();

        $action = new Action();
        $form = $this->createForm(ActionType::class, $action);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                if (!$this->isGranted(ActionVoter::CREATE, $action)) {
                    throw new RuntimeException('You don\'t have permission to create action.');
                }
                $this->actionHelper->saveAction($action);
                $this->userActionLogHelper->addUserActionLog('Created action ' . $action->getName(), $adminUser);
                $this->addFlash('success', 'Action created successfully.');
            } catch (Throwable $throwable) {
                $this->addFlash('error', 'Failed to create action: ' . $throwable->getMessage());
            }

            return $this->redirectToRoute('cp_settings_action');
        }

        return $this->render('cp/settings/action/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{name}/edit', name: 'cp_settings_action_edit')]
    #[IsGranted(ActionVoter::EDIT, 'action')]
    public function edit(Request $request, #[MapEntity(id: 'name')] Action $action): Response
    {
        /** @var User $adminUser*/
        $adminUser = $this->getUser();

        $form = $this->createForm(ActionType::class, $action);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                if (!$this->isGranted(ActionVoter::EDIT, $action)) {
                    throw new RuntimeException('You don\'t have permission to edit action.');
                }
                $this->actionHelper->saveAction($action);
                $this->userActionLogHelper->addUserActionLog('Updated action ' . $action->getName(), $adminUser);
                $this->addFlash('success', 'Action updated successfully.');
            } catch (Throwable $throwable) {
                $this->addFlash('error', 'Failed to update action: ' . $throwable->getMessage());
            }

            return $this->redirectToRoute('cp_settings_action_edit', ['name' => $action->getName()]);
        }


        $actionBackupDockontrolNode = new ActionBackupDockontrolNode();
        $actionBackupDockontrolNode->setParentAction($action);

        $actionBackupDockontrolNodeTypeForm = $this->createForm(ActionBackupDockontrolNodeType::class, $actionBackupDockontrolNode, [
            'parentAction' => $action,
        ]);
        $actionBackupDockontrolNodeTypeForm->handleRequest($request);

        if ($actionBackupDockontrolNodeTypeForm->isSubmitted() && $actionBackupDockontrolNodeTypeForm->isValid()) {
            try {
                if (!$this->isGranted(ActionVoter::CREATE, $actionBackupDockontrolNode)) {
                    throw new RuntimeException('You don\'t have permission to create backup action.');
                }
                $this->actionBackupDockontrolNodeHelper->saveBackupAction($actionBackupDockontrolNode);
                $this->userActionLogHelper->addUserActionLog('Added backup action (' . $actionBackupDockontrolNode->getId() . ')', $adminUser);
                $this->addFlash('success', 'Action updated successfully.');
            } catch (Throwable $throwable) {
                $this->addFlash('danger', 'Failed to update action: ' . $throwable->getMessage());
            }
            return $this->redirectToRoute('cp_settings_action_edit', ['name' => $action->getName()]);
        }

        return $this->render('cp/settings/action/edit.html.twig', [
            'form' => $form->createView(),
            'action' => $action,
            'actionBackupDockontrolNodeTypeForm' => $actionBackupDockontrolNodeTypeForm->createView(),
        ]);
    }

    #[Route('/{name}/delete', name: 'cp_settings_action_delete')]
    #[IsGranted(ActionVoter::DELETE, 'action')]
    public function delete(Request $request, #[MapEntity(id: 'name')] Action $action): JsonResponse
    {
        /** @var User $adminUser*/
        $adminUser = $this->getUser();

        $csrfToken = (string) $request->getPayload()->get('_csrf');
        $errorMessage = null;
        $status = false;
        if (!$this->isCsrfTokenValid('actioncsrf', $csrfToken)) {
            $errorMessage = 'Invalid CSRF token.';
        } else {
            try {
                $this->userActionLogHelper->addUserActionLog('Deleted action ' . $action->getName(), $adminUser);
                $this->actionHelper->removeAction($action);
                $status = true;
                $this->addFlash('danger', 'Action deleted successfully.');
            } catch (Throwable $throwable) {
                $errorMessage = 'Failed to delete Action ' . $throwable->getMessage();
            }
        }

        return $this->json(['status' => $status, 'errorMessage' => $errorMessage]);
    }
}

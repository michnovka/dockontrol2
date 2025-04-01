<?php

declare(strict_types=1);

namespace App\Controller\CP\Settings;

use App\Controller\CP\AbstractCPController;
use App\Entity\ActionBackupDockontrolNode;
use App\Entity\User;
use App\Form\ActionBackupDockontrolNodeType;
use App\Helper\ActionBackupDockontrolNodeHelper;
use App\Helper\UserActionLogHelper;
use App\Security\Voter\ActionBackupDockontrolNodeVoter;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormErrorIterator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;

#[Route('settings/backup-action')]
class ActionBackupDockontrolNodeController extends AbstractCPController
{
    public function __construct(
        private readonly UserActionLogHelper $userActionLogHelper,
        private readonly ActionBackupDockontrolNodeHelper $actionBackupDockontrolNodeHelper,
    ) {
    }

    #[Route('/{id}/delete', name: 'cp_settings_backup_action_delete')]
    #[IsGranted(ActionBackupDockontrolNodeVoter::DELETE, 'actionBackupDockontrolNode')]
    public function deleteBackupAction(
        Request $request,
        ActionBackupDockontrolNode $actionBackupDockontrolNode,
    ): JsonResponse {
        /** @var User $adminUser*/
        $adminUser = $this->getUser();

        $csrfToken = (string) $request->getPayload()->get('_csrf');
        $errorMessage = null;
        $status = false;
        if (!$this->isCsrfTokenValid('backupaction', $csrfToken)) {
            $errorMessage = 'Invalid CSRF token.';
        } else {
            try {
                $this->userActionLogHelper->addUserActionLog('Deleted backup action ' . $actionBackupDockontrolNode->getId(), $adminUser);
                $this->actionBackupDockontrolNodeHelper->removeBackupAction($actionBackupDockontrolNode);
                $status = true;
                $this->addFlash('danger', 'Backup action deleted successfully.');
            } catch (Throwable $throwable) {
                $errorMessage = 'Failed to delete Backup action ' . $throwable->getMessage();
            }
        }

        return $this->json(['status' => $status, 'errorMessage' => $errorMessage]);
    }

    #[Route('/{id}/edit-popup', name: 'cp_settings_backup_action_edit_popup')]
    #[IsGranted(ActionBackupDockontrolNodeVoter::EDIT, 'actionBackupDockontrolNode')]
    public function editPopup(
        Request $request,
        ActionBackupDockontrolNode $actionBackupDockontrolNode,
    ): JsonResponse {
        $form = $this->createForm(ActionBackupDockontrolNodeType::class, $actionBackupDockontrolNode, [
            'parentAction' => $actionBackupDockontrolNode->getParentAction(),
        ]);
        $form->handleRequest($request);
        $content = $this->renderView('cp/settings/action_backup/edit_form.html.twig', [
            'form' => $form->createView(),
        ]);

        return $this->json(['content' => $content]);
    }

    #[Route('/{id}/edit', name: 'cp_settings_backup_action_edit')]
    #[IsGranted(ActionBackupDockontrolNodeVoter::EDIT, 'actionBackupDockontrolNode')]
    public function edit(
        Request $request,
        ActionBackupDockontrolNode $actionBackupDockontrolNode,
    ): JsonResponse {
        $form = $this->createForm(ActionBackupDockontrolNodeType::class, $actionBackupDockontrolNode, [
            'parentAction' => $actionBackupDockontrolNode->getParentAction(),
        ]);
        $form->handleRequest($request);
        $success = false;
        $errorMessage = null;
        /** @var User $adminUser*/
        $adminUser = $this->getUser();

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->actionBackupDockontrolNodeHelper->saveBackupAction($actionBackupDockontrolNode);
                $this->userActionLogHelper->addUserActionLog('Updated backup action ' . $actionBackupDockontrolNode->getId(), $adminUser);
                $this->addFlash('success', 'Backup action updated successfully.');
                $success = true;
            } catch (Throwable $throwable) {
                $errorMessage = 'Failed to update Backup action ' . $throwable->getMessage();
            }
        } else {
            $errorMessages = [];
            foreach ($form->getErrors(true, false) as $error) {
                /** @var FormErrorIterator $error*/
                $currentError = $error->current();
                /** @var FormError $currentError*/
                $errorMessages[] = $currentError->getMessage();
            }

            $errorMessage = implode(', ', $errorMessages);
        }
        return $this->json(['success' => $success, 'errorMessage' => $errorMessage]);
    }
}

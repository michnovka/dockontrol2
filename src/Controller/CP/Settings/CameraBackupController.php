<?php

declare(strict_types=1);

namespace App\Controller\CP\Settings;

use App\Controller\CP\AbstractCPController;
use App\Entity\CameraBackup;
use App\Entity\User;
use App\Form\CameraBackupType;
use App\Helper\CameraBackupHelper;
use App\Helper\UserActionLogHelper;
use App\Security\Voter\CameraBackupVoter;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormErrorIterator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;

#[Route('settings/camera-backup')]
class CameraBackupController extends AbstractCPController
{
    public function __construct(
        private readonly UserActionLogHelper $userActionLogHelper,
        private readonly CameraBackupHelper $cameraBackupHelper,
    ) {
    }

    #[Route('/{id}/delete', name: 'cp_settings_camera_backup_delete')]
    #[IsGranted(CameraBackupVoter::DELETE, 'cameraBackup')]
    public function deleteBackupAction(
        Request $request,
        CameraBackup $cameraBackup,
    ): JsonResponse {
        /** @var User $adminUser*/
        $adminUser = $this->getUser();

        $csrfToken = (string) $request->getPayload()->get('_csrf');
        $errorMessage = null;
        $status = false;
        if (!$this->isCsrfTokenValid('camerabackup', $csrfToken)) {
            $errorMessage = 'Invalid CSRF token.';
        } else {
            try {
                $this->userActionLogHelper->addUserActionLog('Deleted camera backup ' . $cameraBackup->getId(), $adminUser);
                $this->cameraBackupHelper->removeCameraBackup($cameraBackup);
                $status = true;
                $this->addFlash('danger', 'Camera action deleted successfully.');
            } catch (Throwable $throwable) {
                $errorMessage = 'Failed to delete camera backup ' . $throwable->getMessage();
            }
        }

        return $this->json(['status' => $status, 'errorMessage' => $errorMessage]);
    }

    #[Route('/{id}/edit-popup', name: 'cp_settings_camera_backup_edit_popup')]
    #[IsGranted(CameraBackupVoter::EDIT, 'cameraBackup')]
    public function editPopup(
        Request $request,
        CameraBackup $cameraBackup,
    ): JsonResponse {
        $form = $this->createForm(CameraBackupType::class, $cameraBackup, [
            'parentCamera' => $cameraBackup->getParentCamera(),
        ]);
        $form->handleRequest($request);
        $content = $this->renderView('cp/settings/camera_backup/edit_form.html.twig', [
            'form' => $form->createView(),
        ]);

        return $this->json(['content' => $content]);
    }

    #[Route('/{id}/edit', name: 'cp_settings_camera_backup_edit')]
    #[IsGranted(CameraBackupVoter::EDIT, 'cameraBackup')]
    public function edit(
        Request $request,
        CameraBackup $cameraBackup,
    ): JsonResponse {
        $form = $this->createForm(CameraBackupType::class, $cameraBackup, [
            'parentCamera' => $cameraBackup->getParentCamera(),
        ]);
        $form->handleRequest($request);
        $success = false;
        $errorMessage = null;
        /** @var User $adminUser*/
        $adminUser = $this->getUser();

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->cameraBackupHelper->saveCameraBackup($cameraBackup);
                $this->userActionLogHelper->addUserActionLog('Updated camera backup ' . $cameraBackup->getId(), $adminUser);
                $this->addFlash('success', 'Camera backup updated successfully.');
                $success = true;
            } catch (Throwable $throwable) {
                $errorMessage = 'Failed to update camera backup ' . $throwable->getMessage();
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

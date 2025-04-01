<?php

declare(strict_types=1);

namespace App\Controller\CP\Settings;

use App\Controller\CP\AbstractCPController;
use App\Entity\Camera;
use App\Entity\CameraBackup;
use App\Entity\User;
use App\Form\CameraBackupType;
use App\Form\CameraType;
use App\Helper\CameraBackupHelper;
use App\Helper\CameraHelper;
use App\Helper\UserActionLogHelper;
use App\Repository\CameraRepository;
use App\Security\Voter\CameraVoter;
use Knp\Component\Pager\PaginatorInterface;
use RuntimeException;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;

#[Route('/settings/camera')]
class CameraController extends AbstractCPController
{
    public function __construct(
        private readonly CameraRepository $cameraRepository,
        private readonly CameraHelper $cameraHelper,
        private readonly UserActionLogHelper $userActionLogHelper,
        private readonly CameraBackupHelper $cameraBackupHelper,
    ) {
    }

    #[Route('/', name: 'cp_settings_camera')]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function index(Request $request, PaginatorInterface $paginator): Response
    {
        $numberOfRecords = $request->query->getInt('numberOfRecords', 25);
        $queryBuilder = $this->cameraRepository->getQueryBuilder();
        $cameras = $paginator->paginate($queryBuilder, $request->query->getInt('page', 1), $numberOfRecords, [
            'defaultSortFieldName' => 'c.nameId',
            'defaultSortDirection' => 'asc',
        ]);

        return $this->render('cp/settings/camera/index.html.twig', [
            'cameras' => $cameras,
            'numberOfRecords' => $numberOfRecords,
        ]);
    }

    #[Route('/new', name: 'cp_settings_camera_new')]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function new(Request $request): Response
    {
        /** @var User $adminUser*/
        $adminUser = $this->getUser();
        $camera = new Camera();
        $cameraForm = $this->createForm(CameraType::class, $camera);
        $cameraForm->handleRequest($request);
        if ($cameraForm->isSubmitted() && $cameraForm->isValid()) {
            try {
                if (!$this->isGranted(CameraVoter::CREATE, $camera)) {
                    throw new RuntimeException('You don\'t have permission to create camera.');
                }
                $this->cameraHelper->saveCamera($camera);
                $this->userActionLogHelper->addUserActionLog('Created camera ' . $camera->getNameId(), $adminUser);
                $this->addFlash('success', 'Camera created successfully.');
            } catch (Throwable $throwable) {
                $this->addFlash('danger', 'Failed to create camera: ' . $throwable->getMessage());
            }

            return $this->redirectToRoute('cp_settings_camera');
        }

        return $this->render('cp/settings/camera/new.html.twig', [
            'cameraForm' => $cameraForm->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'cp_settings_camera_edit')]
    #[IsGranted(CameraVoter::EDIT, 'camera')]
    public function edit(Request $request, #[MapEntity(id: 'id')] Camera $camera): Response
    {
        /** @var User $adminUser*/
        $adminUser = $this->getUser();
        $cameraForm = $this->createForm(CameraType::class, $camera);
        $cameraForm->handleRequest($request);
        if ($cameraForm->isSubmitted() && $cameraForm->isValid()) {
            try {
                if (!$this->isGranted(CameraVoter::EDIT, $camera)) {
                    throw new RuntimeException('You don\'t have permission to edit camera.');
                }

                $this->cameraHelper->saveCamera($camera);
                $this->userActionLogHelper->addUserActionLog('Updated camera ' . $camera->getNameId(), $adminUser);
                $this->addFlash('success', 'Camera updated successfully.');
            } catch (Throwable $throwable) {
                $this->addFlash('danger', 'Failed to update camera: ' . $throwable->getMessage());
            }

            return $this->redirectToRoute('cp_settings_camera_edit', ['id' => $camera->getNameId()]);
        }

        $cameraBackup = new CameraBackup();
        $cameraBackup->setParentCamera($camera);
        $cameraBackupForm = $this->createForm(CameraBackupType::class, $cameraBackup, [
            'parentCamera' => $camera,
        ]);
        $cameraBackupForm->handleRequest($request);

        if ($cameraBackupForm->isSubmitted() && $cameraBackupForm->isValid()) {
            try {
                if (!$this->isGranted(CameraVoter::CREATE, $cameraBackup)) {
                    throw new RuntimeException('You don\'t have permission to create camera backup.');
                }
                $this->cameraBackupHelper->saveCameraBackup($cameraBackup);
                $this->userActionLogHelper->addUserActionLog('Created camera backup (' . $cameraBackup->getId() . ')', $adminUser);
                $this->addFlash('success', 'Camera backup created successfully.');
            } catch (Throwable $throwable) {
                $this->addFlash('danger', 'Failed to create camera backup: ' . $throwable->getMessage());
            }

            return $this->redirectToRoute('cp_settings_camera_edit', ['id' => $camera->getNameId()]);
        }

        return $this->render('cp/settings/camera/edit.html.twig', [
            'cameraForm' => $cameraForm->createView(),
            'camera' => $camera,
            'cameraBackupForm' => $cameraBackupForm->createView(),
        ]);
    }

    #[Route('/{id}/delete', name: 'cp_settings_camera_delete')]
    #[IsGranted(CameraVoter::DELETE, 'camera')]
    public function delete(Request $request, #[MapEntity(id: 'id')] Camera $camera): JsonResponse
    {
        /** @var User $adminUser*/
        $adminUser = $this->getUser();
        $csrfToken = (string) $request->getPayload()->get('_csrf');
        $errorMessage = null;
        $status = false;
        if (!$this->isCsrfTokenValid('cameracsrf', $csrfToken)) {
            $errorMessage = 'Invalid CSRF token.';
        } else {
            try {
                $this->userActionLogHelper->addUserActionLog('Deleted camera ' . $camera->getNameId(), $adminUser, false);
                $this->cameraHelper->removeCamera($camera);
                $status = true;
                $this->addFlash('danger', 'Camera deleted successfully.');
            } catch (Throwable $throwable) {
                $errorMessage = 'Failed to delete Camera ' . $throwable->getMessage();
            }
        }
        return $this->json(['status' => $status, 'errorMessage' => $errorMessage]);
    }

    #[Route('/show-all', name: 'cp_settings_camera_show_all')]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function showAll(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $cameras = $this->cameraRepository->findAll();

        $cameraSessionId = $this->cameraHelper->checkPermissionAndCreateCameraSession($cameras, $user);

        return $this->render('cp/settings/camera/show_all.html.twig', [
            'cameras' => $cameras,
            'cameraSessionId' => $cameraSessionId,
        ]);
    }
}

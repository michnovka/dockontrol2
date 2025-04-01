<?php

declare(strict_types=1);

namespace App\Controller\CP\Settings;

use App\Controller\CP\AbstractCPController;
use App\Entity\Announcement;
use App\Entity\User;
use App\Form\AnnouncementType;
use App\Helper\AnnouncementHelper;
use App\Helper\UserActionLogHelper;
use App\Repository\AnnouncementRepository;
use App\Security\Voter\AnnouncementVoter;
use RuntimeException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;

#[Route('/setting/announcements')]
class AnnouncementController extends AbstractCPController
{
    public function __construct(
        private readonly AnnouncementRepository $announcementRepository,
        private readonly AnnouncementHelper $announcementHelper,
        private readonly UserActionLogHelper $userActionLogHelper,
    ) {
    }

    #[Route(name: 'cp_settings_announcement')]
    #[IsGranted('ROLE_ADMIN')]
    public function index(): Response
    {
        /** @var User $adminUser*/
        $adminUser = $this->getUser();
        $announcements = $this->announcementRepository->getAnnouncementsForAdmin($adminUser);

        return $this->render('cp/settings/announcement/index.html.twig', [
            'announcements' => $announcements,
        ]);
    }

    #[Route('/new', name: 'cp_settings_announcement_new')]
    #[IsGranted('ROLE_ADMIN')]
    public function new(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $announcement = new Announcement();
        $announcementForm = $this->createForm(AnnouncementType::class, $announcement);
        $announcementForm->handleRequest($request);

        if ($announcementForm->isSubmitted() && $announcementForm->isValid()) {
            try {
                if (!$this->isGranted(AnnouncementVoter::CREATE, $announcement)) {
                    throw new RuntimeException('You don\'t have permission to create announcement.');
                }

                $announcement->setCreatedBy($user);
                $this->announcementHelper->save($announcement);
                $building = $announcement->getBuilding();
                $this->addFlash('success', 'Announcement created successfully.');
                $this->userActionLogHelper->addUserActionLog(
                    'Added announcement ' . $announcement->getSubject() .
                    ($building ? ' for building ' . $building->getName() : ''),
                    $user
                );

                return $this->redirectToRoute('cp_settings_announcement', [], Response::HTTP_SEE_OTHER);
            } catch (Throwable $throwable) {
                $this->addFlash('danger', 'Failed to add announcement, ' . $throwable->getMessage());
            }

            return $this->redirectToRoute('cp_settings_announcement');
        }

        return $this->render('cp/settings/announcement/new.html.twig', [
            'form' => $announcementForm,
        ]);
    }


    #[Route('/{id}/edit', name: 'cp_settings_announcement_edit')]
    #[IsGranted(AnnouncementVoter::EDIT, 'announcement')]
    public function edit(Request $request, Announcement $announcement): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $announcementForm = $this->createForm(AnnouncementType::class, $announcement);
        $announcementForm->handleRequest($request);

        if ($announcementForm->isSubmitted() && $announcementForm->isValid()) {
            try {
                if (!$this->isGranted(AnnouncementVoter::EDIT, $announcement)) {
                    throw new RuntimeException('You don\'t have permission to edit announcement.');
                }

                $this->announcementHelper->save($announcement);
                $building = $announcement->getBuilding();
                $this->userActionLogHelper->addUserActionLog(
                    'Updated announcement ' . $announcement->getSubject() .
                    ($building ? ' for building ' . $building->getName() : ''),
                    $user
                );
                $this->addFlash('success', 'Announcement updated successfully.');
            } catch (Throwable $throwable) {
                $this->addFlash('danger', 'Failed to update announcement, ' . $throwable->getMessage());
            }

            return $this->redirectToRoute('cp_settings_announcement');
        }

        return $this->render('cp/settings/announcement/edit.html.twig', [
            'announcement' => $announcement,
            'form' => $announcementForm,
        ]);
    }

    #[Route('/{id}/delete', name: 'cp_settings_announcement_delete')]
    #[IsGranted(AnnouncementVoter::DELETE, 'announcement')]
    public function delete(Request $request, Announcement $announcement): JsonResponse
    {
        /** @var User $user*/
        $user = $this->getUser();

        $csrfToken = (string) $request->getPayload()->get('_csrf');
        $status = false;
        $errorMessage = null;

        if (!$this->isCsrfTokenValid('announcementcsrf', $csrfToken)) {
            $errorMessage = 'Invalid CSRF token.';
        } else {
            try {
                $deleteLogDesc = 'Deleted announcement #' . $announcement->getId() . ' (' . $announcement->getSubject() . ')';
                $this->announcementHelper->remove($announcement);
                $this->userActionLogHelper->addUserActionLog($deleteLogDesc, $user);
                $status = true;
                $this->addFlash('danger', 'Announcement deleted successfully.');
            } catch (Throwable $throwable) {
                $errorMessage = 'Failed to delete announcement ' . $throwable->getMessage();
            }
        }

        return $this->json(['status' => $status, 'errorMessage' => $errorMessage]);
    }

    #[Route('/bulk-delete', name: 'cp_settings_announcement_bulk_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function bulkDelete(Request $request): JsonResponse
    {
        /** @var User $user*/
        $user = $this->getUser();

        $csrfToken = $request->getPayload()->getString('_csrf');
        $status = false;
        $errorMessage = null;
        $announcementIds = json_decode($request->request->getString('announcementIds'));

        if (!$this->isCsrfTokenValid('announcementcsrf', $csrfToken)) {
            $errorMessage = 'Invalid CSRF token.';
        } else {
            try {
                $this->announcementHelper->bulkDelete($announcementIds);
                $this->userActionLogHelper->addUserActionLog('Deleted ' . count($announcementIds) . ', (' . implode(', ', $announcementIds) . ')' . ' announcements', $user);
                $status = true;
            } catch (Throwable $throwable) {
                $errorMessage = 'Failed to delete announcement ' . $throwable->getMessage();
            }
        }

        return $this->json(['status' => $status, 'errorMessage' => $errorMessage]);
    }
}

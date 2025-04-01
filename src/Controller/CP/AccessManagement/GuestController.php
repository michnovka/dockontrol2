<?php

declare(strict_types=1);

namespace App\Controller\CP\AccessManagement;

use App\Controller\CP\AbstractCPController;
use App\Entity\Enum\UserRole;
use App\Entity\Guest;
use App\Entity\User;
use App\Form\AdminGuestPassType;
use App\Form\Filter\GuestFilterType;
use App\Helper\GuestHelper;
use App\Helper\UserActionLogHelper;
use App\Repository\GuestRepository;
use App\Security\Expression\RoleRequired;
use App\Security\Voter\GuestVoter;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;

/**
 * @psalm-import-type GuestFilterArray from GuestRepository
 */

#[Route('/access-management/guests')]
class GuestController extends AbstractCPController
{
    public function __construct(
        private readonly GuestRepository $guestRepository,
        private readonly UserActionLogHelper $userActionLogHelper,
        private readonly GuestHelper $guestHelper,
    ) {
    }

    #[Route('/', name: 'cp_access_management_guests')]
    #[IsGranted(new RoleRequired(UserRole::ADMIN))]
    public function index(Request $request, PaginatorInterface $paginator): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $numberOfRecords = $request->query->getInt('numberOfRecords', 25);

        $guestFilterForm = $this->createForm(GuestFilterType::class);
        $guestFilterForm->handleRequest($request);

        $filterArr = [];
        if ($guestFilterForm->isSubmitted() && $guestFilterForm->isValid()) {
            $filterArr['user'] = $guestFilterForm->get('user')->getData();
            $filterArr['enabled'] = $guestFilterForm->get('enabled')->getData();
        }

        /** @psalm-var GuestFilterArray $filterArr */
        $queryBuilder = $this->guestRepository->getQueryBuilder($filterArr, $user);
        $guests = $paginator->paginate($queryBuilder, $request->query->getInt('page', 1), $numberOfRecords, [
            'defaultSortFieldName' => 'g.created',
            'defaultSortDirection' => 'desc',
        ]);

        return $this->render('cp/access_management/guest/index.html.twig', [
            'guests' => $guests,
            'numberOfRecords' => $numberOfRecords,
            'guestFilterForm' => $guestFilterForm->createView(),
        ]);
    }

    #[Route('/new', name: 'cp_access_management_guest_new')]
    #[IsGranted(new RoleRequired(UserRole::ADMIN))]
    public function new(Request $request): Response
    {
        /** @var User $adminUser*/
        $adminUser = $this->getUser();

        $guest = new Guest();
        $guestPassForm = $this->createForm(AdminGuestPassType::class, $guest);
        $guestPassForm->handleRequest($request);
        if ($guestPassForm->isSubmitted() && $guestPassForm->isValid()) {
            try {
                if (!$this->isGranted(GuestVoter::CREATE, $guest)) {
                    throw $this->createAccessDeniedException();
                }

                $this->guestHelper->createGuestPass($guest, $guestPassForm->get('expires')->getData());
                $this->userActionLogHelper->addUserActionLog('Created guest pass #' . $guest->getHash()->toString(), $adminUser);
                $this->addFlash('success', 'Guest created successfully.');
            } catch (Throwable $e) {
                $this->addFlash('Failed to create guest, ', $e->getMessage());
            }

            return $this->redirectToRoute('cp_access_management_guests');
        }

        return $this->render('cp/access_management/guest/new.html.twig', [
            'guest' => $guest,
            'guestPassForm' => $guestPassForm->createView(),
        ]);
    }

    #[Route('/{id}/delete', name: 'cp_access_management_guest_delete')]
    #[IsGranted(GuestVoter::DELETE, 'guest')]
    public function delete(Request $request, #[MapEntity(id: 'id')] Guest $guest): JsonResponse
    {
        /** @var User $adminUser*/
        $adminUser = $this->getUser();
        $csrfToken = $request->getPayload()->getString('_csrf');
        $errorMessage = null;
        $status = false;

        if (!$this->isCsrfTokenValid('guestcsrf', $csrfToken)) {
             $errorMessage = 'Invalid CSRF token.';
        } else {
            try {
                $this->userActionLogHelper->addUserActionLog('Deleted guest pass #' . $guest->getHash()->toString(), $adminUser);
                $this->guestHelper->deleteGuestPass($guest);
                $status = true;
                $this->addFlash('danger', 'Guest deleted successfully');
            } catch (Throwable $throwable) {
                $errorMessage = 'Failed to delete User ' . $throwable->getMessage();
            }
        }

        return $this->json(['status' =>  $status, 'errorMessage' => $errorMessage]);
    }

    #[Route('/{id}/restore', name: 'cp_access_management_guest_restore')]
    #[IsGranted(GuestVoter::EDIT, 'guest')]
    public function restore(Request $request, #[MapEntity(id: 'id')] Guest $guest): JsonResponse
    {
        /** @var User $adminUser*/
        $adminUser = $this->getUser();
        $csrfToken = $request->getPayload()->getString('_csrf');
        $errorMessage = null;
        $status = false;

        if (!$this->isCsrfTokenValid('guestcsrf', $csrfToken)) {
            $errorMessage = 'Invalid CSRF token.';
        } else {
            try {
                $this->userActionLogHelper->addUserActionLog('Restored guest pass #' . $guest->getHash()->toString(), $adminUser);
                $this->guestHelper->restoreGuestPass($guest);
                $status = true;
                $this->addFlash('success', 'Guest restored successfully.');
            } catch (Throwable $throwable) {
                $errorMessage = 'Failed to delete user ' . $throwable->getMessage();
            }
        }

        return $this->json(['status' =>  $status, 'errorMessage' => $errorMessage]);
    }
}

<?php

declare(strict_types=1);

namespace App\Controller\CP\AccessManagement;

use App\Controller\CP\AbstractCPController;
use App\Entity\User;
use App\Entity\UserDeletionRequest;
use App\Helper\UserActionLogHelper;
use App\Helper\UserHelper;
use App\Repository\UserDeletionRequestRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;

#[IsGranted('ROLE_SUPER_ADMIN')]
#[Route('/access-management/user-deletion-requests')]
class UserDeletionRequestController extends AbstractCPController
{
    public function __construct(
        private readonly UserDeletionRequestRepository $userDeletionRequestRepository,
        private readonly UserHelper $userHelper,
        private readonly UserActionLogHelper $userActionLogHelper,
    ) {
    }

    #[Route('/', name: 'cp_access_management_user_deletion_requests')]
    public function index(Request $request, PaginatorInterface $paginator): Response
    {
        $numberOfRecords = $request->query->getInt('numberOfRecords', 25);

        $queryBuilder = $this->userDeletionRequestRepository->getQueryBuilder();
        $userDeletionRequests = $paginator->paginate($queryBuilder, $request->query->getInt('page', 1), $numberOfRecords);

        return $this->render('cp/access_management/users_deletion_request/index.html.twig', [
            'userDeletionRequests' => $userDeletionRequests,
            'numberOfRecords' => $numberOfRecords,
        ]);
    }

    #[Route('/{id}/process', name: 'cp_access_management_user_deletion_request_process')]
    public function process(Request $request, UserDeletionRequest $userDeletionRequest): JsonResponse
    {
        /** @var User $adminUser*/
        $adminUser = $this->getUser();
        $csrfToken = $request->getPayload()->getString('_csrf');
        $isApprovedDeletion = $request->getPayload()->getBoolean('is_approved');
        $errorMessage = null;
        $status = false;
        if (!$this->isCsrfTokenValid('deleteaccountprocess', $csrfToken)) {
            $errorMessage = 'Invalid CSRF token.';
        } else {
            try {
                $this->userActionLogHelper->addUserActionLog('Process user account deletion request #' . $userDeletionRequest->getId() . ' for user #' . $userDeletionRequest->getUser()->getId() . ' (' . $userDeletionRequest->getUser()->getEmail() . ')', $adminUser);
                if ($isApprovedDeletion) {
                    $this->userHelper->deleteUser($userDeletionRequest->getUser());
                } else {
                    $this->userHelper->deleteAccountDeletionRequestAndEnableUserAccount($userDeletionRequest);
                }
                $status = true;
                $this->addFlash('danger', 'User account deleted successfully.');
            } catch (Throwable $throwable) {
                $errorMessage = 'Failed to delete User account, ' . $throwable->getMessage();
            }
        }
        return $this->json(['status' => $status, 'errorMessage' => $errorMessage]);
    }
}

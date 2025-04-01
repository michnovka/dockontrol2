<?php

declare(strict_types=1);

namespace App\Controller\CP\AccessManagement;

use App\Controller\CP\AbstractCPController;
use App\Entity\Enum\UserRole;
use App\Entity\SignupCode;
use App\Entity\User;
use App\Form\Filter\SignupCodeFilterType;
use App\Form\SignupCodeType;
use App\Helper\SignupCodeHelper;
use App\Helper\UserActionLogHelper;
use App\Repository\SignupCodeRepository;
use App\Security\Expression\RoleRequired;
use App\Security\Voter\SignupCodeVoter;
use Knp\Component\Pager\PaginatorInterface;
use RuntimeException;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;

/**
 * @psalm-import-type signupCodeFilterArray from SignupCodeRepository
 */
#[Route('/access-management/signup-codes')]
class SignupCodeController extends AbstractCPController
{
    public function __construct(
        private readonly SignupCodeRepository $signupCodeRepository,
        private readonly SignupCodeHelper $signupCodeHelper,
        private readonly UserActionLogHelper $userActionLogHelper,
    ) {
    }

    #[Route('/', name: 'cp_access_management_signup_code')]
    #[IsGranted(new RoleRequired(UserRole::ADMIN))]
    public function index(Request $request, PaginatorInterface $paginator): Response
    {
        /** @var User $adminUser*/
        $adminUser = $this->getUser();
        $numberOfRecords = $request->query->getInt('numberOfRecords', 25);

        $isSuperAdmin = $adminUser->getRole() === UserRole::SUPER_ADMIN;
        $signupCodeFilterType = $this->createForm(SignupCodeFilterType::class, null, [
            'is_super_admin' => $isSuperAdmin,
        ]);
        $signupCodeFilterType->handleRequest($request);

        $filter = [];

        if ($signupCodeFilterType->isSubmitted()) {
            $filter['hash'] = $signupCodeFilterType->get('hash')->getData();
            $filter['status'] = $signupCodeFilterType->get('status')->getData();
            $filter['timeCreated'] = $signupCodeFilterType->get('timeCreated')->getData();
            $filter['timeExpires'] = $signupCodeFilterType->get('timeExpires')->getData();
            $filter['timeUsed'] = $signupCodeFilterType->get('timeUsed')->getData();
            if ($isSuperAdmin) {
                $filter['admin'] = $signupCodeFilterType->get('admin')->getData();
                $filter['building'] = $signupCodeFilterType->get('building')->getData();
                $filter['apartment'] = $signupCodeFilterType->get('apartment')->getData();
            }
        }

        /** @psalm-var signupCodeFilterArray $filter*/
        $queryBuilder = $this->signupCodeRepository->getQueryBuilderForAdmin($adminUser, $filter);
        $signupCodes = $paginator->paginate($queryBuilder, $request->query->getInt('page', 1), $numberOfRecords, [
            'defaultSortFieldName' => 'sc.createdTime',
            'defaultSortDirection' => 'desc',
        ]);

        return $this->render('cp/access_management/signup_code/index.html.twig', [
            'numberOfRecords' => $numberOfRecords,
            'signupCodes' => $signupCodes,
            'signupCodeFilterType' => $signupCodeFilterType,
            'is_super_admin' => $isSuperAdmin,
        ]);
    }

    #[Route('/new', name: 'cp_access_management_signup_code_new')]
    #[IsGranted(new RoleRequired(UserRole::ADMIN))]
    public function new(Request $request): Response
    {
        /** @var User $adminUser*/
        $adminUser = $this->getUser();
        $signupCode = new SignupCode();
        $signupCode->setAdminUser($adminUser);
        $signupCodeForm = $this->createForm(SignupCodeType::class, $signupCode, [
            'admin_user' => $adminUser,
        ]);
        $signupCodeForm->handleRequest($request);

        if ($signupCodeForm->isSubmitted() && $signupCodeForm->isValid()) {
            try {
                if (!$this->isGranted(SignupCodeVoter::CREATE, $signupCode)) {
                    throw new RuntimeException('You don\'t have permission to create signup code.');
                }

                $this->signupCodeHelper->saveSignupCode($signupCode);
                $this->userActionLogHelper->addUserActionLog('Created signup code ' . $signupCode->getHash()->toString() . ' for apartment ' . $signupCode->getApartment()->getBuilding()->getName(), $adminUser);
                $this->addFlash('success', 'Signup code created successfully.');
            } catch (Throwable $e) {
                $this->addFlash('danger', 'Failed to create signup code: ' . $e->getMessage());
            }
            return $this->redirectToRoute('cp_access_management_signup_code');
        }

        return $this->render('cp/access_management/signup_code/new.html.twig', [
            'signupCodeForm' => $signupCodeForm->createView(),
        ]);
    }

    #[Route('/{hash}/delete', name: 'cp_access_management_signup_code_delete')]
    #[IsGranted(SignupCodeVoter::DELETE, 'signupCode')]
    public function delete(
        Request $request,
        #[MapEntity(id: 'hash')] SignupCode $signupCode,
    ): JsonResponse {
        /** @var User $adminUser*/
        $adminUser = $this->getUser();
        $csrfToken = $request->request->getString('_csrf');
        $status = false;

        if (!$this->isCsrfTokenValid('signupcodecsrf', $csrfToken)) {
            $errorMessage = 'Invalid CSRF token.';
        } else {
            try {
                $description = 'Deleted signup code ' . $signupCode->getHash()->toString() . ' for building ' . $signupCode->getApartment()->getBuilding()->getName();
                $this->signupCodeHelper->deleteSignupCode($signupCode);
                $this->userActionLogHelper->addUserActionLog($description, $adminUser);
                $status = true;
                $this->addFlash('danger', 'Signup code deleted successfully.');
                $errorMessage = 'Signup code deleted successfully.';
            } catch (Throwable $throwable) {
                $errorMessage = 'Failed to delete Signup code ' . $throwable->getMessage();
            }
        }

        return $this->json(['status' => $status, 'errorMessage' => $errorMessage]);
    }
}

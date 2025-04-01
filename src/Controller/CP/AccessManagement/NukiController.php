<?php

declare(strict_types=1);

namespace App\Controller\CP\AccessManagement;

use App\Controller\CP\AbstractCPController;
use App\Entity\Enum\UserRole;
use App\Entity\Nuki;
use App\Entity\User;
use App\Form\Filter\NukiFilterType;
use App\Form\NukiType;
use App\Helper\NukiHelper;
use App\Helper\UserActionLogHelper;
use App\Repository\NukiRepository;
use App\Security\Expression\RoleRequired;
use App\Security\Voter\NukiVoter;
use Knp\Component\Pager\PaginatorInterface;
use RuntimeException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;

/** @psalm-import-type NukiFilterArray from NukiRepository */

#[Route('/access-management/nuki')]
class NukiController extends AbstractCPController
{
    public function __construct(
        private readonly NukiRepository $nukiRepository,
        private readonly NukiHelper $nukiHelper,
        private readonly UserActionLogHelper $userActionLogHelper,
    ) {
    }

    #[Route('/', name: 'cp_access_management_nukis')]
    #[IsGranted(new RoleRequired(UserRole::ADMIN))]
    public function index(Request $request, PaginatorInterface $paginator): Response
    {
        /** @var User $adminUser*/
        $adminUser = $this->getUser();

        $numberOfRecords = $request->query->getInt('numberOfRecords', 25);
        $nukiFilterForm = $this->createForm(NukiFilterType::class);
        $nukiFilterForm->handleRequest($request);
        $filter = [];

        if ($nukiFilterForm->isSubmitted() && $nukiFilterForm->isValid()) {
            $filter['user'] = $nukiFilterForm->get('user')->getData();
            $filter['name'] = $nukiFilterForm->get('name')->getData();
        }

        /** @psalm-var NukiFilterArray $filter */
        $queryBuilder = $this->nukiRepository->getQueryBuilder($adminUser, $filter);
        $nukis = $paginator->paginate($queryBuilder, $request->query->getInt('page', 1), $numberOfRecords);

        return $this->render('cp/access_management/nuki/index.html.twig', [
            'nukis' => $nukis,
            'numberOfRecords' => $numberOfRecords,
            'nukiFilterForm' => $nukiFilterForm->createView(),
        ]);
    }

    #[Route('/new', name: 'cp_access_management_nuki_new')]
    #[IsGranted(new RoleRequired(UserRole::ADMIN))]
    public function new(Request $request): Response
    {
        /** @var User $adminUser*/
        $adminUser = $this->getUser();

        $nuki = new Nuki();
        $nukiForm = $this->createForm(NukiType::class, $nuki, [
            'show_pin' => $this->isGranted('ROLE_SUPER_ADMIN'),
        ]);
        $nukiForm->handleRequest($request);

        if ($nukiForm->isSubmitted() && $nukiForm->isValid()) {
            try {
                if (!$this->isGranted(NukiVoter::CREATE, $nuki)) {
                    throw new RuntimeException('You don\'t have permission to create nuki.');
                }
                $password1 = $nukiForm->get('password1')->getData();
                $pin = null;
                if ($this->isGranted('ROLE_SUPER_ADMIN')) {
                    $pin = $nukiForm->get('pin')->getData();
                }
                $this->nukiHelper->saveNuki($nuki, $password1, $pin);
                $descriptionForAdminLog = 'Created nuki #' . $nuki->getId() . ' (' . $nuki->getName() . ')' . ' for user ' . $nuki->getUser()->getId() . ' (' . $nuki->getUser()->getName() . ')';
                $this->userActionLogHelper->addUserActionLog($descriptionForAdminLog, $adminUser);
            } catch (Throwable $throwable) {
                $this->addFlash('danger', 'Failed to create nuki, ' . $throwable->getMessage());
            }

            return $this->redirectToRoute('cp_access_management_nukis');
        }
        return $this->render('cp/access_management/nuki/new.html.twig', [
            'nukiForm' => $nukiForm->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'cp_access_management_nuki_edit')]
    #[IsGranted(NukiVoter::EDIT, 'nuki')]
    public function edit(Request $request, Nuki $nuki): Response
    {
        /** @var User $adminUser*/
        $adminUser = $this->getUser();

        $nukiForm = $this->createForm(NukiType::class, $nuki, [
            'required_password' => false,
            'show_pin' => $this->isGranted('ROLE_SUPER_ADMIN'),
        ]);
        $nukiForm->handleRequest($request);

        if ($nukiForm->isSubmitted() && $nukiForm->isValid()) {
            try {
                if (!$this->isGranted(NukiVoter::EDIT, $nuki)) {
                    throw new RuntimeException('You don\'t have permission to manage nuki.');
                }
                $password1 = $nukiForm->get('password1')->getData();
                $pin = null;
                if ($this->isGranted('ROLE_SUPER_ADMIN')) {
                    $pin = $nukiForm->get('pin')->getData();
                }
                $this->nukiHelper->saveNuki($nuki, $password1, $pin);
                $descriptionForAdminLog = 'Updated nuki #' . $nuki->getId() . ' (' . $nuki->getName() . ')' . ' for user ' . $nuki->getUser()->getId() . ' (' . $nuki->getUser()->getName() . ')';
                $this->userActionLogHelper->addUserActionLog($descriptionForAdminLog, $adminUser);
            } catch (Throwable $throwable) {
                $this->addFlash('danger', 'Failed to create nuki, ' . $throwable->getMessage());
            }
            return $this->redirectToRoute('cp_access_management_nukis');
        }
        return $this->render('cp/access_management/nuki/edit.html.twig', [
            'nukiForm' => $nukiForm->createView(),
            'nuki' => $nuki,
        ]);
    }

    #[Route('/{id}/delete', name: 'cp_access_management_nuki_delete')]
    #[IsGranted(NukiVoter::DELETE, 'nuki')]
    public function delete(Request $request, Nuki $nuki): JsonResponse
    {
        /** @var User $adminUser*/
        $adminUser = $this->getUser();
        $csrfToken = (string) $request->getPayload()->get('_csrf');
        $errorMessage = null;
        $status = false;
        if (!$this->isCsrfTokenValid('nukicsrf', $csrfToken)) {
            $errorMessage = 'Invalid CSRF token.';
        } else {
            try {
                $descriptionForAdminLog = 'Deleted nuki #' . $nuki->getId() . ' (' . $nuki->getName() . ')' . ' from user ' . $nuki->getUser()->getId() . ' (' . $nuki->getUser()->getName() . ')';
                $this->nukiHelper->deleteNuki($nuki);
                $this->userActionLogHelper->addUserActionLog($descriptionForAdminLog, $adminUser);
                $status = true;
                $this->addFlash('danger', 'Nuki deleted successfully.');
            } catch (Throwable $throwable) {
                $errorMessage = 'Failed to delete Nuki ' . $throwable->getMessage();
            }
        }
        return $this->json(['status' => $status, 'errorMessage' => $errorMessage]);
    }

    #[Route('/{id}/remove-pin', name: 'cp_access_management_nuki_remove_pin')]
    #[IsGranted(NukiVoter::MANAGE_PIN, 'nuki')]
    public function removePin(Request $request, Nuki $nuki): JsonResponse
    {
        /** @var User $adminUser*/
        $adminUser = $this->getUser();
        $csrfToken = (string) $request->getPayload()->get('_csrf');
        $errorMessage = null;
        $status = false;
        if (!$this->isCsrfTokenValid('nukicsrf', $csrfToken)) {
            $errorMessage = 'Invalid CSRF token.';
        } else {
            try {
                $descriptionForAdminLog = 'Removed PIN From nuki #' . $nuki->getId() . ' (' . $nuki->getName() . ')';
                $this->nukiHelper->removeNukiPin($nuki);
                $this->userActionLogHelper->addUserActionLog($descriptionForAdminLog, $adminUser);
                $status = true;
                $this->addFlash('success', 'PIN removed successfully.');
            } catch (Throwable $throwable) {
                $errorMessage = 'Failed to remove pin ' . $throwable->getMessage();
            }
        }
        return $this->json(['status' => $status, 'errorMessage' => $errorMessage]);
    }
}

<?php

declare(strict_types=1);

namespace App\Controller\PZ\Settings;

use App\Controller\PZ\AbstractPZController;
use App\Entity\Enum\UserRole;
use App\Entity\User;
use App\Form\TenantType;
use App\Helper\UserActionLogHelper;
use App\Helper\UserHelper;
use App\Repository\UserRepository;
use App\Security\Voter\UserVoter;
use RuntimeException;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;

#[Route('/settings/apartment')]
#[IsGranted('ROLE_LANDLORD')]
class ApartmentController extends AbstractPZController
{
    public function __construct(
        private readonly UserHelper $userHelper,
        private readonly UserRepository $userRepository,
        private readonly TranslatorInterface $translator,
        private readonly UserActionLogHelper $userActionLogHelper,
    ) {
    }

    #[Route('/', 'dockontrol_settings_apartment')]
    #[IsGranted('ROLE_LANDLORD')]
    public function index(): Response
    {
        /** @var User $currentUser*/
        $currentUser = $this->getUser();

        if (empty($currentUser->getApartment())) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('pz/settings/index.html.twig', [
            'tab' => 'apartment',
        ]);
    }

    #[Route('/tenant/create', name: 'dockontrol_settings_tenant_create')]
    public function createTenant(Request $request): Response
    {
        /** @var User $createdBy */
        $createdBy = $this->getUser();
        $tenant = new User();
        $tenant->setRole(UserRole::TENANT);
        $tenant->setApartment($createdBy->getApartment());
        $tenant->setLandlord($createdBy);

        $form = $this->createForm(TenantType::class, $tenant);
        $form->handleRequest($request);

        try {
            if ($request->isMethod('GET')) {
                return new Response(
                    $this->renderView('pz/settings/sections/tenant_form.html.twig', [
                        'form' => $form->createView(),
                    ])
                );
            }

            if ($request->isMethod('POST')) {
                if (!$this->validateCsrf($request)) {
                    $errorMessage = $this->translator->trans('dockontrol.global.invalid_csrf_token');
                    return $this->json(['status' => false, 'errorMessage' => $errorMessage], Response::HTTP_BAD_REQUEST);
                } else {
                    if ($form->isValid() && $form->isSubmitted()) {
                        $email = $form->get('email')->getData();
                        $password = $form->get('password')->getData();
                        $emailCheck = $this->checkEmailExists($email);
                        if (!$emailCheck['status']) {
                            return $this->json($emailCheck, Response::HTTP_BAD_REQUEST);
                        }

                        if (!$this->isGranted(UserVoter::CREATE, $tenant)) {
                            throw new RuntimeException($this->translator->trans('dockontrol.settings.apartment.tenant.messages.do_not_have_permission_to_create'));
                        }

                        $this->userHelper->saveUser($tenant, $password, $createdBy);
                        $this->userActionLogHelper->addUserActionLog('Created tenant #' . $tenant->getId() . ' (' . $tenant->getEmail() . ')', $createdBy);
                        $this->addFlash('success', $this->translator->trans('dockontrol.settings.apartment.tenant.messages.successfully_created'));

                        return $this->json(['status' => true, 'errorMessage' => null], Response::HTTP_OK);
                    } else {
                        $errors = [];
                        foreach ($form->getErrors(true) as $error) {
                            $formOrigin = $error->getOrigin();
                            if ($error->getCause() && $formOrigin) {
                                $errors[$formOrigin->getName()][] = $error->getMessage();
                            } else {
                                $errors['global'][] = $error->getMessage();
                            }
                        }
                        return $this->json(['status' => false, 'errors' => $errors], Response::HTTP_BAD_REQUEST);
                    }
                }
            }
        } catch (Throwable $throwable) {
            return $this->json(['status' => false, 'errorMessage' => $this->translator->trans('dockontrol.settings.apartment.tenant.messages.failed_to_create') . $throwable->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return $this->json(['status' => false], Response::HTTP_BAD_REQUEST);
    }

    #[Route('/tenant/{id}/edit', name: 'dockontrol_settings_tenant_edit', methods: ['GET', 'POST'])]
    #[IsGranted(UserVoter::MANAGE, 'tenant')]
    public function editTenant(
        Request $request,
        #[MapEntity(id: 'id')] User $tenant,
    ): Response {
        /** @var User $user*/
        $user = $this->getUser();
        $profileFields = ['phone', 'email', 'name', 'enabled'];
        $originalData = [];
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        foreach ($profileFields as $field) {
            $originalData[$field] = $propertyAccessor->getValue($tenant, $field);
        }

        $form = $this->createForm(TenantType::class, $tenant, [
            'show_edit_form_fields' => false,
        ]);
        $form->handleRequest($request);

        try {
            if ($request->isMethod('GET')) {
                return new Response(
                    $this->renderView('pz/settings/sections/tenant_form.html.twig', [
                        'form' => $form->createView(),
                        'show_edit_form_fields' => false,
                    ])
                );
            }

            if ($request->isMethod('POST')) {
                if ($form->isValid() && $form->isSubmitted()) {
                    $email = $form->get('email')->getData();
                    $password = $form->get('password')->getData();
                    $emailCheck = $this->checkEmailExists($email, $tenant);
                    if (!$emailCheck['status']) {
                        return $this->json($emailCheck, Response::HTTP_BAD_REQUEST);
                    }
                    if (!$this->isGranted(UserVoter::MANAGE, $tenant)) {
                        throw new RuntimeException($this->translator->trans('dockontrol.settings.apartment.tenant.messages.do_not_have_permission_to_edit'));
                    }
                    $changes = [];
                    foreach ($profileFields as $field) {
                        $oldValue = $originalData[$field];
                        $newValue = $propertyAccessor->getValue($tenant, $field);
                        if ($oldValue != $newValue) {
                            if (is_bool($oldValue) && is_bool($newValue)) {
                                $oldValue = $oldValue ? 'yes' : 'no';
                                $newValue = $newValue ? 'yes' : 'no';
                            } else {
                                $oldValue = (string) $oldValue;
                                $newValue = (string) $newValue;
                            }

                            $changes[$field] = [
                                'from' => $oldValue,
                                'to' => $newValue,
                            ];
                        }
                    }

                    $description = sprintf(
                        'Updated tenant #%d (%s): ',
                        $tenant->getId(),
                        $tenant->getEmail()
                    );

                    foreach ($changes as $field => $values) {
                        $description .= sprintf("%s from %s to %s, ", $field, $values['from'], $values['to']);
                    }

                    $this->userHelper->saveUser($tenant, $password);
                    $this->userActionLogHelper->addUserActionLog($description, $user);
                    $this->addFlash('success', $this->translator->trans('dockontrol.settings.apartment.tenant.messages.successfully_updated'));
                    return $this->json(['status' => true, 'errorMessage' => null], Response::HTTP_OK);
                } else {
                    $errors = [];
                    foreach ($form->getErrors(true) as $error) {
                        $formOrigin = $error->getOrigin();
                        if ($error->getCause() && $formOrigin) {
                            $errors[$formOrigin->getName()][] = $error->getMessage();
                        } else {
                            $errors['global'][] = $error->getMessage();
                        }
                    }
                    return $this->json(['status' => false, 'errors' => $errors], Response::HTTP_BAD_REQUEST);
                }
            }
        } catch (Throwable $throwable) {
            return $this->json(['status' => false, 'errorMessage' => $this->translator->trans('dockontrol.settings.apartment.tenant.messages.failed_to_update') . $throwable->getMessage()], Response::HTTP_BAD_REQUEST);
        }
        return $this->json(['status' => false], Response::HTTP_BAD_REQUEST);
    }

    #[Route('/tenant/{id}/delete', name: 'dockontrol_settings_tenant_delete')]
    #[IsGranted(UserVoter::DELETE, 'tenant')]
    public function deleteTenant(Request $request, #[MapEntity(id: 'id')] User $tenant): JsonResponse
    {
        /** @var User $user*/
        $user = $this->getUser();
        $errorMessage = null;
        $status = false;
        if (!$this->validateCsrf($request)) {
            $errorMessage = $this->translator->trans('dockontrol.global.invalid_csrf_token');
        } else {
            try {
                $this->userActionLogHelper->addUserActionLog('deleted tenant #' . $tenant->getId() . ' (' . $tenant->getEmail() . ')', $user);
                $this->userHelper->deleteUser($tenant);
                $status = true;
                $this->addFlash('danger', $this->translator->trans('dockontrol.settings.apartment.tenant.messages.successfully_deleted'));
            } catch (Throwable $throwable) {
                $errorMessage = $this->translator->trans('dockontrol.settings.apartment.tenant.messages.tenant_deleted_failed') . $throwable->getMessage();
            }
        }
        return $this->json(['status' => $status, 'errorMessage' => $errorMessage]);
    }

    private function validateCsrf(Request $request): bool
    {
        $csrf = $request->request->getString('_csrf');
        return $this->isCsrfTokenValid('tenantcsrf', $csrf);
    }

    /**
     * @return array{status: bool, errorMessage?: string}
     */
    private function checkEmailExists(string $email, ?User $tenant = null): array
    {
        $existingUser = $this->userRepository->findOneBy(['email' => $email]);
        if ($existingUser && $existingUser !== $tenant) {
            return ['status' => false, 'errorMessage' => $this->translator->trans('dockontrol.settings.apartment.tenant.messages.email_already_exist')];
        }

        return ['status' => true];
    }
}

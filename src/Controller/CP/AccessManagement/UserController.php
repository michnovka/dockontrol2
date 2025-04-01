<?php

declare(strict_types=1);

namespace App\Controller\CP\AccessManagement;

use App\Controller\CP\AbstractCPController;
use App\Controller\CP\SearchAPIInterface;
use App\Entity\Action;
use App\Entity\Enum\UserRole;
use App\Entity\User;
use App\Form\Filter\UserFilterType;
use App\Form\ManageGroupType;
use App\Form\ResetPasswordType;
use App\Form\UserRoleType;
use App\Form\UserType;
use App\Helper\CarEnterDetailsHelper;
use App\Helper\GroupHelper;
use App\Helper\UserActionLogHelper;
use App\Helper\UserHelper;
use App\Repository\ActionRepository;
use App\Repository\CarEnterDetailsRepository;
use App\Repository\UserRepository;
use App\Security\Expression\RoleRequired;
use App\Security\Voter\UserVoter;
use Doctrine\ORM\EntityManagerInterface;
use Elao\Enum\ReadableEnumInterface;
use Knp\Component\Pager\PaginatorInterface;
use Override;
use RuntimeException;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;

/**
 * @psalm-import-type UserFilterArray from UserRepository
 */

#[Route('/access-management/users')]
class UserController extends AbstractCPController implements SearchAPIInterface
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UserHelper $userHelper,
        private readonly CarEnterDetailsRepository $carEnterDetailsRepository,
        private readonly ActionRepository $actionRepository,
        private readonly CarEnterDetailsHelper $carEnterDetailsHelper,
        private readonly UserActionLogHelper $userActionLogHelper,
        private readonly GroupHelper $groupHelper,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/', name: 'cp_access_management_users')]
    #[IsGranted(new RoleRequired(UserRole::ADMIN))]
    public function index(Request $request, PaginatorInterface $paginator): Response
    {
        $numberOfRecords = $request->query->getInt('numberOfRecords', 25);
        /** @var User $adminUser*/
        $adminUser = $this->getUser();

        $userFilterForm = $this->createForm(UserFilterType::class);
        $userFilterForm->handleRequest($request);
        $filterArr = [];

        if ($userFilterForm->isSubmitted() && $userFilterForm->isValid()) {
            $filterArr['name'] = $userFilterForm->get('name')->getData();
            $filterArr['email'] = $userFilterForm->get('email')->getData();
            $filterArr['phone'] = $userFilterForm->get('phone')->getData();
            $filterArr['apartment'] = $userFilterForm->get('apartment')->getData();
            $filterArr['group'] = $userFilterForm->get('group')->getData();
            $filterArr['role'] = $userFilterForm->get('role')->getData();
            $filterArr['landlord'] = $userFilterForm->get('landlord')->getData();
        }

        /** @psalm-var UserFilterArray $filterArr */
        $queryBuilder = $this->userRepository->getQueryBuilder($adminUser, filter: $filterArr);
        $users = $paginator->paginate($queryBuilder, $request->query->getInt('page', 1), $numberOfRecords, [
            'defaultSortFieldName' => 'u.id',
            'defaultSortDirection' => 'asc',
        ]);

        return $this->render('cp/access_management/user/index.html.twig', [
            'users' => $users,
            'numberOfRecords' => $numberOfRecords,
            'userFilterForm' => $userFilterForm->createView(),
        ]);
    }

    #[Route('/new', name: 'cp_access_management_user_new')]
    #[IsGranted(new RoleRequired(UserRole::ADMIN))]
    public function new(Request $request): Response
    {
        /** @var User $adminUser*/
        $adminUser = $this->getUser();
        $user = new User();
        $form = $this->createForm(UserType::class, $user, [
            'user_role_choices' => $adminUser->getRole() === UserRole::SUPER_ADMIN ? 'all' : 'limited',
            'is_create_new_form' => true,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $email = $form->get('email')->getData();
                $checkForExistingUser = $this->userRepository->findOneBy(['email' => $email]);

                if ($checkForExistingUser) {
                    $this->addFlash('danger', 'User already exists.');
                    return $this->redirectToRoute('cp_access_management_users');
                }

                if (!$this->isGranted(UserVoter::CREATE, $user)) {
                    throw new RuntimeException('You don\'t have permission to create user.');
                }

                /** @var User $createdBy */
                $createdBy = $this->getUser();
                $password = $form->get('password')->getData();
                $this->userHelper->saveUser($user, $password, $createdBy);
                $this->userActionLogHelper->addUserActionLog('Created user #' . $user->getId() . ' (' . $user->getEmail() . ')', $createdBy);
                $this->addFlash('success', 'User created successfully.');
                return  $this->redirectToRoute('cp_access_management_users');
            } catch (Throwable $throwable) {
                $this->addFlash('danger', 'Failed to create user ' . $throwable->getMessage());
            }
        }
        return $this->render('cp/access_management/user/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'cp_access_management_user_edit')]
    #[IsGranted(UserVoter::MANAGE, 'user')]
    public function edit(Request $request, #[MapEntity(id: 'id')] User $user): Response
    {
        /** @var User $adminUser*/
        $adminUser = $this->getUser();
        $carEnterDetails = $this->carEnterDetailsRepository->findBy(['user' => $user], ['order' => 'ASC']);
        $userAccountIsEnabled = $user->isEnabled();
        $allActions = $this->actionRepository->getActionsExceptCarEnterExit();

        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        $metadata = $this->entityManager->getClassMetadata(User::class);
        $fieldNames = $metadata->getFieldNames();
        $originalData = [];
        $oldUserRole = $user->getRole();
        foreach ($fieldNames as $field) {
            $originalData[$field] = $propertyAccessor->getValue($user, $field);
        }

        $form = $this->createForm(UserType::class, $user, [
            'edit_password' => true,
            'allow_edit_field' => !($this->isGranted('ROLE_SUPER_ADMIN')),
            'apartment' => $user->getApartment(),
            'allowEnabledCheckbox' => $user->getId() === $adminUser->getId(),
        ]);

        $passwordForm = $this->createForm(ResetPasswordType::class, null, [
            'show_password_label' => true,
        ]);

        $manageGroupForm = $this->createForm(ManageGroupType::class, null, [
            'groups' => $user->getGroups()->getValues(),
            'disabled_edit' => !($this->isGranted('ROLE_SUPER_ADMIN')) || $user->getRole() === UserRole::TENANT,
        ]);

        $userRoleForm = $this->createForm(UserRoleType::class, $user, [
            'user_role_choices' => $adminUser->getRole() === UserRole::SUPER_ADMIN ? 'all' : 'limited',
        ]);

        $form->handleRequest($request);
        $passwordForm->handleRequest($request);
        $manageGroupForm->handleRequest($request);
        $userRoleForm->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                if (!$this->isGranted(UserVoter::MANAGE, $user)) {
                    throw new RuntimeException('You don\'t have permission to edit user.');
                }

                if ($user->getId() === $adminUser->getId() && $user->isEnabled() !== $userAccountIsEnabled) {
                    throw new RuntimeException('You cannot disable or enable your own account.');
                }

                $changes = [];
                foreach ($fieldNames as $field) {
                    $oldValue = $originalData[$field];
                    $newValue = $propertyAccessor->getValue($user, $field);
                    if ($oldValue != $newValue) {
                        if (is_bool($oldValue) && is_bool($newValue)) {
                            $oldValue = $oldValue ? 'yes' : 'no';
                            $newValue = $newValue ? 'yes' : 'no';
                        } elseif ($oldValue instanceof ReadableEnumInterface && $newValue instanceof ReadableEnumInterface) {
                            $oldValue = $oldValue->getReadable();
                            $newValue = $newValue->getReadable();
                        } elseif ($oldValue instanceof Action && $newValue instanceof Action) {
                            $oldValue = $oldValue->getName();
                            $newValue = $newValue->getName();
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
                    'Updated user #%d (%s): ',
                    $user->getId(),
                    $user->getEmail()
                );

                foreach ($changes as $field => $values) {
                    $description .= sprintf("%s from %s to %s, ", $field, $values['from'], $values['to']);
                }

                $this->userHelper->saveUser($user);
                $this->addFlash('success', 'User updated successfully.');
                if (!empty($changes)) {
                    $this->userActionLogHelper->addUserActionLog($description, $adminUser);
                }
            } catch (Throwable $throwable) {
                $this->addFlash('danger', 'Failed to update user ' . $throwable->getMessage());
            }
            return  $this->redirectToRoute('cp_access_management_user_edit', ['id' => $user->getId()]);
        }

        if ($passwordForm->isSubmitted() && $passwordForm->isValid()) {
            $password = $passwordForm->get('password')->getData();
            try {
                if (!$this->isGranted(UserVoter::MANAGE, $user)) {
                    throw new RuntimeException('You don\'t have permission to edit user.');
                }

                $this->userHelper->saveUser($user, $password);
                $this->userActionLogHelper->addUserActionLog('Updated password for user #' . $user->getId() . ' (' . $user->getEmail() . ')', $adminUser);
                $this->addFlash('success', 'Password updated successfully.');
            } catch (Throwable $throwable) {
                $this->addFlash('danger', 'Failed to update password ' . $throwable->getMessage());
            }
            return  $this->redirectToRoute('cp_access_management_user_edit', ['id' => $user->getId()]);
        }

        if ($manageGroupForm->isSubmitted() && $manageGroupForm->isValid()) {
            $groups = $manageGroupForm->get('groups')->getData();
            try {
                if (!$this->isGranted(UserVoter::MANAGE_USER_GROUP, $user)) {
                    throw new RuntimeException('You don\'t have permission to manage groups.');
                }
                $this->groupHelper->updateGroupsForUserOrPermission($user, $groups, $adminUser);
                $this->addFlash('success', 'Groups updated successfully.');
            } catch (Throwable $throwable) {
                $this->addFlash('danger', 'Failed to update group, ' . $throwable->getMessage());
            }

            return $this->redirectToRoute('cp_access_management_user_edit', ['id' => $user->getId()]);
        }

        if ($userRoleForm->isSubmitted() && $userRoleForm->isValid()) {
            try {
                if (!$this->isGranted(UserVoter::MANAGE, $user)) {
                    throw new RuntimeException('You don\'t have permission to set this user role.');
                }
                $newUserRole = $user->getRole();
                $description = sprintf(
                    'Updated user #%d (%s): ',
                    $user->getId(),
                    $user->getEmail()
                );
                $description .= sprintf("user role from %s to %s, ", $oldUserRole->getReadable(), $newUserRole->getReadable());
                $this->userHelper->saveUser($user);
                $this->addFlash('success', 'User role updated successfully.');
                $this->userActionLogHelper->addUserActionLog($description, $adminUser);
            } catch (Throwable $throwable) {
                $this->addFlash('danger', 'Failed to update user role' . $throwable->getMessage());
            }
            return  $this->redirectToRoute('cp_access_management_user_edit', ['id' => $user->getId()]);
        }

        return $this->render('cp/access_management/user/edit.html.twig', [
            'form' => $form->createView(),
            'passwordForm' => $passwordForm->createView(),
            'user' => $user,
            'carEnterDetails' => $carEnterDetails,
            'allActions' => $allActions,
            'manageGroupForm' => $manageGroupForm->createView(),
            'disabledManageGroupFormSubmit' => $user->getRole() === UserRole::TENANT,
            'userRoleForm' => $userRoleForm->createView(),
        ]);
    }

    #[Route('/{id}/delete', name: 'cp_access_management_user_delete')]
    #[IsGranted(UserVoter::DELETE, 'user')]
    public function delete(Request $request, #[MapEntity(id: 'id')] User $user): JsonResponse
    {
        /** @var User $adminUser*/
        $adminUser = $this->getUser();
        $csrfToken = (string) $request->getPayload()->get('_csrf');
        $errorMessage = null;
        $status = false;
        if (!$this->isCsrfTokenValid('usercsrf', $csrfToken)) {
            $errorMessage = 'Invalid CSRF token.';
        } else {
            try {
                $this->userActionLogHelper->addUserActionLog('Deleted user #' . $user->getId() . ' (' . $user->getEmail() . ')', $adminUser);
                $this->userHelper->deleteUser($user);
                $status = true;
                $this->addFlash('danger', 'User deleted successfully.');
            } catch (Throwable $throwable) {
                $errorMessage = 'Failed to delete User ' . $throwable->getMessage();
            }
        }
        return $this->json(['status' => $status, 'errorMessage' => $errorMessage]);
    }

    #[Route('/search-api', name: 'cp_access_management_user_search_api')]
    #[IsGranted(new RoleRequired(UserRole::ADMIN))]
    #[Override]
    public function searchAPI(Request $request): JsonResponse
    {
        /** @var User $adminUser*/
        $adminUser = $this->getUser();
        $searchText = (string) $request->query->get('searchText');
        $users = $this->userRepository->searchUser($searchText, $adminUser);

        $usersArr = [];

        if (!empty($users)) {
            foreach ($users as $user) {
                $userData = [];
                $userData['id'] = $user->getId();
                $userData['title'] = $user->getName() . ' ( ' . $user->getEmail() . ' ) ';
                $userData['text'] = $user->getTwigDisplayValue();

                $usersArr[] = $userData;
            }
        }

        return $this->json([
            'items' => $usersArr,
            'totalCount' => count($usersArr),
        ], Response::HTTP_OK);
    }

    #[Route('/{id}/{action}/add-car-enter-detail', name: 'cp_access_management_user_add_car_enter_detail')]
    #[IsGranted(UserVoter::MANAGE, 'user')]
    public function addCarEnterDetail(
        Request $request,
        #[MapEntity(id: 'id')] User $user,
        #[MapEntity(id: 'action')] Action $action,
    ): JsonResponse {
        /** @var User $adminUser */
        $adminUser = $this->getUser();
        $status = false;
        $csrfToken = $request->request->getString('_csrf');
        $waitSecondsAfterEnter = $request->request->getInt('wait_seconds_after_enter');
        $waitSecondsAfterExit = $request->request->getInt('wait_seconds_after_exit');

        if (!$this->isCsrfTokenValid('carenterdetailscsrf', $csrfToken)) {
            $errorMessage = 'Invalid CSRF token.';
        } else {
            try {
                if (!$this->isGranted(UserVoter::MANAGE, $user)) {
                    throw new RuntimeException('You don\'t have permission to edit user.');
                }

                $this->carEnterDetailsHelper->saveCarEnterDetails($action, $waitSecondsAfterEnter, $waitSecondsAfterExit, user: $user);
                $this->userActionLogHelper->addUserActionLog('Added car details for user #' . $user->getId() . ' (' . $user->getEmail() . ')', $adminUser);
                $status = true;
                $this->addFlash('success', 'Car enter detail created successfully.');
                $errorMessage = 'Car enter detail created successfully.';
            } catch (Throwable $throwable) {
                $errorMessage = 'Failed to add car enter detail, ' . $throwable->getMessage();
                $this->addFlash('danger', $errorMessage);
            }
        }

        return $this->json(['status' => $status, 'errorMessage' => $errorMessage]);
    }

    #[Route('/{id}/email-verification/{onOrOff}', name: 'cp_access_management_user_email_verification_on_or_off', requirements: ['onOrOff' => 'on|off'])]
    #[IsGranted(UserVoter::MARK_EMAIL_VERIFIED, 'user')]
    public function updateEmailVerification(
        Request $request,
        #[MapEntity(id: 'id')] User $user,
        string $onOrOff,
    ): JsonResponse {
        /** @var User $adminUser*/
        $adminUser = $this->getUser();
        $csrfToken = $request->getPayload()->getString('_csrf');
        $errorMessage = null;
        $status = false;
        $isVerify = $onOrOff === 'on';
        $action = $isVerify ? 'Verify' : 'Unverify';

        if (!$this->isCsrfTokenValid('usercsrf', $csrfToken)) {
            $errorMessage = 'Invalid CSRF token.';
        } else {
            try {
                $this->userHelper->updateEmailVerification($user, $isVerify);
                if ($isVerify) {
                    $description = 'Verified user #' . $user->getId() . ' (' . $user->getEmail() . ')';
                    $flashMessage = 'User verified successfully.';
                } else {
                    $description = 'Unverified user #' . $user->getId() . ' (' . $user->getEmail() . ')';
                    $flashMessage = 'User unverified successfully.';
                }
                $this->userActionLogHelper->addUserActionLog($description, $adminUser);
                $status = true;
                $this->addFlash('success', $flashMessage);
            } catch (Throwable $throwable) {
                $errorMessage = 'Failed to ' . $action . ' user ' . $throwable->getMessage();
            }
        }

        return $this->json(['status' => $status, 'errorMessage' => $errorMessage]);
    }

    #[Route('/{id}/phone-verification/{onOrOff}', name: 'cp_access_management_user_phone_verification_on_or_off', requirements: ['onOrOff' => 'on|off'])]
    #[IsGranted(UserVoter::MARK_PHONE_VERIFIED, 'user')]
    public function updatePhoneVerification(
        Request $request,
        #[MapEntity(id: 'id')] User $user,
        string $onOrOff,
    ): JsonResponse {
        /** @var User $adminUser*/
        $adminUser = $this->getUser();
        $csrfToken = $request->getPayload()->getString('_csrf');
        $errorMessage = null;
        $status = false;
        $isVerify = $onOrOff === 'on';
        $action = $isVerify ? 'Verify' : 'Unverify';

        if (!$this->isCsrfTokenValid('usercsrf', $csrfToken)) {
            $errorMessage = 'Invalid CSRF token.';
        } else {
            try {
                $this->userHelper->updatePhoneVerification($user, $isVerify);
                if ($isVerify) {
                    $description = 'Verified user phone #' . $user->getId() . ' (' . $user->getPhone() . ')';
                    $flashMessage = 'User phone number verified successfully.';
                } else {
                    $description = 'Unverified user phone #' . $user->getId() . ' (' . $user->getPhone() . ')';
                    $flashMessage = 'User phone number unverified successfully.';
                }
                $this->userActionLogHelper->addUserActionLog($description, $adminUser);
                $status = true;
                $this->addFlash('success', $flashMessage);
            } catch (Throwable $throwable) {
                $errorMessage = 'Failed to ' . $action . ' user ' . $throwable->getMessage();
            }
        }

        return $this->json(['status' => $status, 'errorMessage' => $errorMessage]);
    }
}

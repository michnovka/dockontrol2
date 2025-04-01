<?php

declare(strict_types=1);

namespace App\Controller\PZ\Settings;

use App\Controller\PZ\AbstractPZController;
use App\Entity\APIKey;
use App\Entity\User;
use App\Form\ChangeEmailType;
use App\Form\ChangePasswordType;
use App\Form\UserPublicSettingsType;
use App\Helper\APIKeyHelper;
use App\Helper\ButtonHelper;
use App\Helper\MailerHelper;
use App\Helper\UserActionLogHelper;
use App\Helper\UserHelper;
use App\Repository\ActionQueueRepository;
use App\Repository\APIKeyRepository;
use App\Repository\Log\CameraLogRepository;
use App\Security\Voter\APIKeyVoter;
use App\Security\Voter\UserVoter;
use Elao\Enum\ReadableEnumInterface;
use RuntimeException;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;

#[Route('/settings')]
#[IsGranted('ROLE_TENANT')]
class SettingsController extends AbstractPZController
{
    public function __construct(
        private readonly UserHelper $userHelper,
        private readonly APIKeyHelper $apiKeyHelper,
        private readonly APIKeyRepository $apiKeyRepository,
        private readonly ButtonHelper $buttonHelper,
        private readonly MailerHelper $mailerHelper,
        private readonly TranslatorInterface $translator,
        private readonly UserActionLogHelper $userActionLogHelper,
        private readonly ActionQueueRepository $actionQueueRepository,
        private readonly CameraLogRepository $cameraLogRepository,
    ) {
    }

    #[Route('/my-profile', name: 'dockontrol_settings_my_profile')]
    public function profile(Request $request): Response
    {
        /** @var User $user*/
        $user = $this->getUser();
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        $profileFields = ['phone', 'buttonPressType', 'name', 'buttonPressType', 'email'];
        $originalData = [];
        foreach ($profileFields as $field) {
            $originalData[$field] = $propertyAccessor->getValue($user, $field);
        }

        $userPublicSettingsTypeForm = $this->createForm(UserPublicSettingsType::class, $user, [
            'show_car_enter_exit' => $user->isCarEnterExitAllowed(),
        ]);
        $userPublicSettingsTypeForm->handleRequest($request);

        $changeUserEmailForm = $this->createForm(ChangeEmailType::class, $user, [
            'current_user_email' => $user->getEmail(),
        ]);
        $changeUserEmailForm->handleRequest($request);

        $changePasswordForm = $this->createForm(ChangePasswordType::class);
        $changePasswordForm->handleRequest($request);

        if ($userPublicSettingsTypeForm->isSubmitted() && $userPublicSettingsTypeForm->isValid()) {
            try {
                if (!$this->isGranted(UserVoter::EDIT, $user)) {
                    throw new RuntimeException($this->translator->trans('dockontrol.settings.my_profile.messages.not_allowed_to_edit_profile'));
                }
                $changes = [];
                foreach ($profileFields as $field) {
                    $oldValue = $originalData[$field];
                    $newValue = $propertyAccessor->getValue($user, $field);
                    if ($oldValue != $newValue) {
                        if (is_bool($oldValue) && is_bool($newValue)) {
                            $oldValue = $oldValue ? 'yes' : 'no';
                            $newValue = $newValue ? 'yes' : 'no';
                        } elseif ($oldValue instanceof ReadableEnumInterface && $newValue instanceof ReadableEnumInterface) {
                            $oldValue = $oldValue->getReadable();
                            $newValue = $newValue->getReadable();
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
                    'Updated profile #%d (%s): ',
                    $user->getId(),
                    $user->getEmail()
                );

                foreach ($changes as $field => $values) {
                    $description .= sprintf("%s from %s to %s, ", $field, $values['from'], $values['to']);
                }

                $this->userHelper->saveUser($user);
                $this->userActionLogHelper->addUserActionLog($description, $user);
                $this->addFlash('success', $this->translator->trans('dockontrol.settings.my_profile.messages.contact_info_saved'));
            } catch (Throwable $throwable) {
                $this->addFlash('danger', 'Failed to save contact information, ' . $throwable->getMessage());
            }

            return $this->redirectToRoute('dockontrol_settings_my_profile');
        }

        if ($changeUserEmailForm->isSubmitted() && $changeUserEmailForm->isValid()) {
            $userEmail = $changeUserEmailForm->get('email')->getData();
            if ($user->getEmail() !== $userEmail) {
                $ipAddress = $request->getClientIp();
                $browser = $request->headers->get('User-Agent');
                if (empty($ipAddress) || empty($browser)) {
                    throw new RuntimeException($this->translator->trans('dockontrol.settings.change_email.messages.ip_address_and_browser_can_not_be_null'));
                }
                $emailChangeLog = $this->userHelper->requestEmailChange($user, $user->getEmail(), $userEmail);
                try {
                    $this->mailerHelper->sendEmailChangeConfirmation($emailChangeLog, $ipAddress, $browser);
                    $flashMessage = $this->translator->trans('dockontrol.settings.change_email.messages.email_change_request_created');
                    if ($user->isEmailVerified()) {
                        $flashMessage .= $this->translator->trans('dockontrol.settings.change_email.messages.verify_old_and_new_email_to_complete_change');
                    } else {
                        $flashMessage .= $this->translator->trans('dockontrol.settings.change_email.messages.verify_new_email_to_complete_change');
                    }
                    $this->addFlash('success', $flashMessage);
                } catch (Throwable $e) {
                    $this->addFlash('danger', 'Failed to create e-mail change request, ' . $e->getMessage());
                }

                return $this->redirectToRoute('dockontrol_settings_my_profile');
            }
        }

        if ($changePasswordForm->isSubmitted() && $changePasswordForm->isValid()) {
            try {
                if (!$this->isGranted(UserVoter::EDIT, $user)) {
                    throw new RuntimeException($this->translator->trans('dockontrol.settings.change_password.messages.now_allowed_to_change_password'));
                }
                $newPassword = $changePasswordForm->get('newPassword')->getData();
                $this->userHelper->saveUser($user, $newPassword);
                $this->userActionLogHelper->addUserActionLog('changed account password', $user);
                $this->addFlash('success', $this->translator->trans('dockontrol.settings.change_password.messages.password_reset_successfully'));
            } catch (Throwable $throwable) {
                $this->addFlash('danger', 'Failed to reset password, ' . $throwable->getMessage());
            }

            return $this->redirectToRoute('dockontrol_settings_my_profile');
        }


        return $this->render('pz/settings/index.html.twig', [
            'userPublicSettingsTypeForm' => $userPublicSettingsTypeForm->createView(),
            'changeEmailForm' => $changeUserEmailForm->createView(),
            'changePasswordForm' => $changePasswordForm->createView(),
            'tab' => 'my_profile',
        ]);
    }

    #[Route('/nuki', name: 'dockontrol_settings_nuki')]
    public function nuki(): Response
    {
        return $this->render('pz/settings/index.html.twig', [
            'tab' => 'nuki',
        ]);
    }

    #[Route('/api', name: 'dockontrol_settings_api')]
    public function apiKeys(Request $request): Response
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();
        $showPrivateKeyFirstTime = $request->getSession()->get('SHOW_PRIVATE_KEY_FIRST_TIME_PZ', false);
        $publicKeyHash = $request->getSession()->get('API_KEY_PUBLIC_HASH_PZ');
        $userButtons = $this->buttonHelper->getUserButtonsSeparatedByTypes($currentUser)['userButtons'];
        $apiKeyFromSession = null;

        if ($showPrivateKeyFirstTime) {
            $apiKeyFromSession = $this->apiKeyRepository->find($publicKeyHash);
            $request->getSession()->remove('SHOW_PRIVATE_KEY_FIRST_TIME_PZ');
            $request->getSession()->remove('API_KEY_PUBLIC_HASH_PZ');
        }

        return $this->render('pz/settings/index.html.twig', [
            'tab' => 'api_keys',
            'apiKeyFromSession' => $apiKeyFromSession,
            'showPrivateKeyFirstTime' => $showPrivateKeyFirstTime,
            'userButtons' => $userButtons,
        ]);
    }

    #[Route('/api-keys/new', name: 'dockontrol_api_key_new')]
    public function index(Request $request): JsonResponse
    {
        $status = false;
        $errorMessage = null;
        $csrfToken = $request->request->getString('_csrf');
        $apiKeyName = $request->request->getString('name');
        /** @var User $user*/
        $user = $this->getUser();

        if (!$this->isCsrfTokenValid('apikeycsrf', $csrfToken)) {
            $errorMessage = $this->translator->trans('dockontrol.global.invalid_csrf_token');
        } else {
            try {
                $apiKey = new APIKey();
                $apiKey->setName($apiKeyName);
                $apiKey->setUser($user);
                if (!$this->isGranted(APIKeyVoter::CREATE, $apiKey)) {
                    throw new RuntimeException($this->translator->trans('dockontrol.settings.api.api_keys.messages.dont_have_permission_to_create_api_keys'));
                }
                $this->apiKeyHelper->saveAPIKey($apiKey);
                $description = 'Created API key ' . $apiKey->getPublicKey()->toString();
                $this->userActionLogHelper->addUserActionLog($description, $user);
                $status = true;
                $this->addFlash('success', $this->translator->trans('dockontrol.settings.api.api_keys.messages.api_key_created'));
                $request->getSession()->set('SHOW_PRIVATE_KEY_FIRST_TIME_PZ', true);
                $request->getSession()->set('API_KEY_PUBLIC_HASH_PZ', $apiKey->getPublicKey());
            } catch (Throwable $throwable) {
                $errorMessage = 'Failed to create API key, ' . $throwable->getMessage();
            }
        }

        return $this->json(
            [
                'status' => $status,
                'message' => $errorMessage,
            ]
        );
    }

    #[Route('/api-keys/{publicKey}/delete', name: 'cp_access_management_api_keys_delete')]
    #[IsGranted(APIKeyVoter::DELETE, 'apiKey')]
    public function delete(Request $request, #[MapEntity(id: 'publicKey')] APIKey $apiKey): JsonResponse
    {
        /** @var User $user*/
        $user = $this->getUser();
        $status = false;
        $errorMessage = null;
        $csrfToken = $request->request->getString('_csrf');

        if (!$this->isCsrfTokenValid('apikeycsrf', $csrfToken)) {
            $errorMessage = $this->translator->trans('dockontrol.global.invalid_csrf_token');
        } else {
            try {
                $description = 'Deleted API key ' . $apiKey->getPublicKey()->toString();
                $this->userActionLogHelper->addUserActionLog($description, $user);
                $this->apiKeyHelper->deleteAPIKey($apiKey);
                $status = true;
                $this->addFlash('danger', $this->translator->trans('dockontrol.settings.api.api_keys.messages.api_key_deleted'));
            } catch (Throwable $exception) {
                $errorMessage = 'Failed to delete API key' . $exception->getMessage();
            }
        }

        return $this->json(['status' => $status, 'message' => $errorMessage]);
    }

    #[Route('/custom-sorting', name: 'dockontrol_settings_custom_sorting')]
    public function customSorting(): Response
    {
        /** @var User $user*/
        $user = $this->getUser();
        $buttonsSeparatedByTypes = $this->buttonHelper->getUserButtonsSeparatedByTypes($user);
        $buttons = $buttonsSeparatedByTypes['userButtons'];
        $nameConflicts = $buttonsSeparatedByTypes['nameConflicts'];
        $customSortingGroups = $user->getCustomSortingGroups();

        return $this->render('pz/settings/index.html.twig', [
            'tab' => 'custom_sorting',
            'buttons' => $buttons,
            'nameConflicts' => $nameConflicts,
            'customSortingGroups' => $customSortingGroups,
        ]);
    }

    #[Route('/custom-sorting/enable', 'dockontrol_settings_custom_sorting_enable')]
    public function enableCustomSorting(): RedirectResponse
    {
        /** @var User $user*/
        $user = $this->getUser();
        $this->userHelper->enableCustomSorting($user);
        $this->userActionLogHelper->addUserActionLog('enabled custom sorting', $user);
        return $this->redirectToRoute('dockontrol_settings_custom_sorting');
    }

    #[Route('/custom-sorting/disable', 'dockontrol_settings_custom_sorting_disable')]
    public function disableCustomSorting(): RedirectResponse
    {
        /** @var User $user*/
        $user = $this->getUser();
        $this->userHelper->disableCustomSorting($user);
        $this->userActionLogHelper->addUserActionLog('disabled custom sorting', $user);
        return $this->redirectToRoute('dockontrol_settings_custom_sorting');
    }

    #[Route('/gdpr', name: 'dockontrol_settings_gdpr')]
    public function gdpr(): Response
    {
        /** @var User $currentUser*/
        $currentUser = $this->getUser();

        $lastUserActionLogs = $this->actionQueueRepository->getActionQueuesForUser($currentUser, 10);
        $lastUserCameraLogs = $this->cameraLogRepository->getCameraLogsForUser($currentUser, 10);

        if ($this->isGranted('ROLE_LANDLORD')) {
            $associatedUserAccounts = $currentUser->getTenants();
        } else {
            $associatedUserAccounts = [$currentUser->getLandlord()];
        }

        return $this->render('pz/settings/index.html.twig', [
            'tab' => 'gdpr',
            'lastUserActionLogs' => $lastUserActionLogs,
            'lastUserCameraLogs' => $lastUserCameraLogs,
            'associatedUserAccounts' => $associatedUserAccounts,
        ]);
    }
}

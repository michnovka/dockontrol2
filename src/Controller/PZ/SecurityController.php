<?php

declare(strict_types=1);

namespace App\Controller\PZ;

use App\Entity\User;
use App\Event\LocaleEvent;
use App\Form\ForgotPasswordType;
use App\Form\ResetPasswordType;
use App\Helper\CameraHelper;
use App\Helper\MailerHelper;
use App\Helper\SecurityHelper;
use App\Helper\UserHelper;
use App\Repository\CameraRepository;
use App\Repository\UserRepository;
use Carbon\CarbonImmutable;
use LogicException;
use RuntimeException;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;

#[IsGranted('PUBLIC_ACCESS')]
class SecurityController extends AbstractPZController
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UserHelper $userHelper,
        private readonly MailerHelper $mailerHelper,
        private readonly CameraHelper $cameraHelper,
        private readonly CameraRepository $cameraRepository,
        private readonly SecurityHelper $securityHelper,
        private readonly TranslatorInterface $translator,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly UserPasswordHasherInterface $userPasswordHasher,
    ) {
    }
    #[Route(path: '/login', name: 'dockontrol_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser() && $this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->redirectToRoute('dockontrol_main');
        }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('pz/security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    #[Route(path: '/logout', name: 'dockontrol_logout')]
    public function logout(): void
    {
        throw new LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route(path: '/forgot-password', name: 'dockontrol_forgot_password')]
    public function forgotPassword(Request $request): Response
    {
        if ($this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->redirectToRoute('dockontrol_main');
        }

        $form = $this->createForm(ForgotPasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $email = $form->get('email')->getData();
            $user = $this->userRepository->findOneBy(['email' => $email]);

            if ($user instanceof User) {
                try {
                    $ipAddress = $request->getClientIp();
                    $browser = $request->headers->get('User-Agent');
                    $requestedTime = CarbonImmutable::now();
                    if (empty($ipAddress) || empty($browser)) {
                        throw new RuntimeException($this->translator->trans('dockontrol.settings.change_email.messages.ip_address_and_browser_can_not_be_null'));
                    }
                    $this->mailerHelper->sendResetPasswordMail($user, $ipAddress, $browser, $requestedTime);
                    $this->addFlash('success', $this->translator->trans('dockontrol.security.messages.mails_sent_your_respective_email'));
                    return $this->redirectToRoute('dockontrol_login');
                } catch (Throwable $throwable) {
                    $this->addFlash('danger', $this->translator->trans('dockontrol.security.messages.failed_to_send_email') . $throwable->getMessage());
                }
            }
            return $this->redirectToRoute('dockontrol_forgot_password');
        }

        return $this->render('pz/security/forgot_password.html.twig', [
            'form' => $form->createView(),
            'error' => null,
        ]);
    }

    #[Route(path: '/reset-password/{token}', name: 'dockontrol_reset_password')]
    public function resetPassword(Request $request, string $token): Response
    {
        if ($this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->redirectToRoute('dockontrol_main');
        }

        $user = $this->userRepository->findOneBy(['resetPasswordToken' => $token]);

        if (!$user instanceof User || $user->getResetPasswordTokenTimeExpires() < CarbonImmutable::now()) {
            $this->addFlash('danger', $this->translator->trans('dockontrol.security.messages.password_link_expired'));

            return $this->redirectToRoute('dockontrol_login');
        }

        $form = $this->createForm(ResetPasswordType::class, null, [
            'show_password_label' => false,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('password')->getData();
            $this->userHelper->resetPassword($user, $plainPassword);
            $this->addFlash('success', $this->translator->trans('dockontrol.security.messages.password_updated_successfully'));
            return $this->redirectToRoute('dockontrol_login');
        }

        return $this->render('pz/security/reset_password.html.twig', [
            'form' => $form->createView(),
            'error' => null,
        ]);
    }

    #[Route('/get-camera-session', name: 'dockontrol_camera_get_camera_session')]
    #[IsGranted('ROLE_TENANT')]
    public function getCameraSession(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $cameras = $request->request->get('cameras');

        try {
            if (!empty($cameras) && is_string($cameras)) {
                $cameras = json_decode($cameras);
            }

            if (!is_array($cameras) || empty($cameras)) {
                return $this->json([
                    'success' => false,
                    'message' => $this->translator->trans('dockontrol.security.messages.no_camera_provide'),
                ]);
            }
            $cameraObjArr = $this->cameraRepository->findBy(['nameId' => $cameras]);

            $cameraSessionId = $this->cameraHelper->checkPermissionAndCreateCameraSession($cameraObjArr, $user);
        } catch (Throwable $throwable) {
            throw new RuntimeException($this->translator->trans('dockontrol.security.messages.failed_generate_camera_action') . $throwable->getMessage());
        }

        return $this->json([
            'success' => true,
            'camera_session_id' => $cameraSessionId,
        ]);
    }

    #[Route('/request-email-verification', name: 'dockontrol_request_email_verification', methods: ['POST'])]
    #[IsGranted('ROLE_TENANT')]
    public function requestEmailVerification(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $csrfToken = $request->request->getString('_csrf');
        $success = false;

        try {
            if (!$this->isCsrfTokenValid('dockontrolrequestemailverification', $csrfToken)) {
                throw new RuntimeException($this->translator->trans('dockontrol.global.invalid_csrf_token'));
            }
            $this->mailerHelper->sendVerificationEmail($user);
            $this->addFlash('success', $this->translator->trans('dockontrol.security.messages.new_email_verification_link_has_been_sent_to_your_email'));
            $success = true;
        } catch (Throwable $e) {
            $this->addFlash('danger', $this->translator->trans('dockontrol.security.messages.failed_to_send_verification_email') . $e->getMessage());
        }

        return $this->json([
            'success' => $success,
        ]);
    }

    #[Route('/verify-email/{id}/{uuid}/{hash}', name: 'dockontrol_verify_email', methods: ['GET'])]
    public function verifyEmail(User $user, string $uuid, string $hash): Response
    {
        try {
            $this->securityHelper->processVerifyEmailLink($user, $uuid, $hash);
            $this->addFlash('success', $this->translator->trans('dockontrol.security.email_verification.messages.success'));
        } catch (Throwable $e) {
            $this->addFlash('danger', $this->translator->trans('dockontrol.security.email_verification.messages.failed') . $e->getMessage());
        }
        return $this->redirectToRoute('dockontrol_main');
    }

    #[Route('/confirm-email-change/{oldOrNew}/{hash}', name: 'dockontrol_confirm_email_change', requirements: ['oldOrNew' => 'old|new'], methods: ['GET'])]
    public function confirmEmailChange(string $oldOrNew, string $hash): Response
    {
        $status = true;
        $message = $this->translator->trans('dockontrol.security.email_confirmation.messages.success');
        $isOldHash = $oldOrNew === 'old';

        try {
            $this->securityHelper->verifyEmailChangeHash($hash, $isOldHash);
        } catch (Throwable $exception) {
            $status = false;
            $message = $this->translator->trans('dockontrol.security.email_confirmation.messages.failed') . $exception->getMessage();
        }

        return $this->render('pz/security/confirm_email_change.html.twig', [
            'status' => $status,
            'message' => $message,
        ]);
    }

    #[Route('/switch-language', name: 'pz_change_locale')]
    public function index(Request $request): Response
    {
        $defaultLocale = $request->getDefaultLocale();
        $locale = $request->request->getString('locale', $defaultLocale);

        $localCookie = new Cookie('locale', $locale, time() + (365 * 24 * 3600), '/', null, false, true);
        $response = new Response();
        $response->headers->setCookie($localCookie);
        $response->sendHeaders();
        $this->eventDispatcher->dispatch(new LocaleEvent($locale, $request));

        return $this->redirect($request->headers->get('referer') ?? '');
    }

    #[Route('/validate-nuki-password2', name: 'pz_validate_nuki_password2')]
    #[IsGranted('ROLE_TENANT')]
    public function validateNukiPassword2(Request $request): JsonResponse
    {
        $password2 = $request->request->getString('password2');
        $csrfToken = $request->request->getString('_csrf');
        $errorMessage = null;
        $isValidPassword = false;
        /** @var User $currentUser*/
        $currentUser = $this->getUser();

        /*
        if ($this->isCsrfTokenValid('nukicsrf', $csrfToken)) {
            $passwordMatchWithUserPassword = $this->userPasswordHasher->isPasswordValid($currentUser, $password2);
            if (!$passwordMatchWithUserPassword) {
                $isValidPassword = true;
            } else {
                $errorMessage = $this->translator->trans('dockontrol.settings.nuki.messages.can_not_use_this_password_because_it_is_current_password');
            }
        } else {
            $errorMessage = $this->translator->trans('dockontrol.global.invalid_csrf_token');
        }
        */
        $isValidPassword = true;

        return $this->json(['is_password_valid' => $isValidPassword, 'message' => $errorMessage]);
    }

    #[Route(path: '/terms-of-service', name: 'dockontrol_terms_of_service')]
    public function termsOfService(): Response
    {
        return $this->render('pz/security/terms_of_service.html.twig');
    }

    #[Route('/account-deletion-request', name: 'dockontrol_account_deletion_request', methods: ['POST'])]
    #[IsGranted('ROLE_TENANT')]
    public function requestAccountDeletion(Request $request): Response
    {
        /** @var User $currentUser*/
        $currentUser = $this->getUser();
        $errorMessage = null;
        $success = false;

        if (!$request->isXmlHttpRequest()) {
            $errorMessage = $this->translator->trans('dockontrol.global.invalid_request');
        } else {
            $csrfToken = $request->request->getString('_csrf');
            try {
                if (!$this->isCsrfTokenValid('deleteaccountrequest', $csrfToken)) {
                    $errorMessage = $this->translator->trans('dockontrol.global.invalid_csrf_token');
                } else {
                    $this->mailerHelper->sendAccountDeletionRequestEmail($currentUser);
                    $success = true;
                }
                $this->addFlash('success', $this->translator->trans('dockontrol.settings.gdpr.request_sent'));
            } catch (Throwable $e) {
                $errorMessage = $this->translator->trans('dockontrol.settings.gdpr.request_sent_error');
                $this->addFlash('danger', $errorMessage . ' ' . $e->getMessage());
            }
        }

        return $this->json(['success' => $success, 'errorMessage' => $errorMessage]);
    }

    #[Route('/verify-account-deletion-request/{id}/{uuid}/{hash}', name: 'dockontrol_verify_account_deletion_request', methods: ['GET'])]
    public function verifyAccountDeletionRequest(
        User $user,
        string $uuid,
        string $hash,
    ): Response {
        try {
            $this->securityHelper->processAccountDeletionRequest($user, $uuid, $hash);
            $this->addFlash('success', $this->translator->trans('dockontrol.settings.gdpr.delete_account_success'));
        } catch (Throwable $e) {
            $this->addFlash('danger', $this->translator->trans('dockontrol.settings.gdpr.delete_account_failed') . $e->getMessage());
        }
        return $this->redirectToRoute('dockontrol_main');
    }
}

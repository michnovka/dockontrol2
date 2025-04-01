<?php

declare(strict_types=1);

namespace App\Security\EventSubscriber;

use App\Controller\CP\AbstractCPController;
use App\Controller\PZ\AbstractPZController;
use App\Controller\PZ\SecurityController;
use App\Controller\PZ\SignupController;
use App\Entity\User;
use App\Helper\SecurityHelper;
use Carbon\CarbonImmutable;
use Override;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Bundle\SecurityBundle\Security\FirewallConfig;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\RememberMeAuthenticator;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Component\Security\Http\RememberMe\RememberMeHandlerInterface;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

readonly class UserAuthenticationSubscriber implements EventSubscriberInterface
{
    use TargetPathTrait;

    private const array FIREWALLS = ['main'];

    public function __construct(
        #[Autowire('@security.user_checker_locator')] private ServiceLocator $serviceLocator,
        private UrlGeneratorInterface $urlGenerator,
        private Security $security,
        private LoggerInterface $logger,
        private SecurityHelper $securityHelper,
        private TokenStorageInterface $tokenStorage,
        private RememberMeHandlerInterface $rememberMeHandler,
    ) {
    }

    /**
     * @inheritdoc
     */
    #[Override]
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => ['onKernelController', 4],
            KernelEvents::REQUEST => ['onKernelRequest', 2],
            LoginSuccessEvent::class => 'onLoginSuccess',
            LoginFailureEvent::class => 'onLoginFailure',
        ];
    }


    public function onKernelRequest(RequestEvent $requestEvent): void
    {
        $firewallConfig = $this->security->getFirewallConfig($requestEvent->getRequest());
        $firewallName = $firewallConfig?->getName();

        // Check if the event should be handled for this firewall
        if (!in_array($firewallName, self::FIREWALLS)) {
            return;
        }

        $currentUser = $this->tokenStorage->getToken()?->getUser();

        if ($currentUser instanceof User && $firewallConfig instanceof FirewallConfig) {
            try {
                $serviceArr = explode('.', $firewallConfig->getUserChecker());
                $serviceName = end($serviceArr);
                /** @var UserCheckerInterface $userCheckerService*/
                $userCheckerService = $this->serviceLocator->get($serviceName);
                // this returns the service ID of the user checker. I did not figure out how to dynamically load it then
                // but I would like to do so. possibly use service locator?
                $userCheckerService->checkPreAuth($currentUser);
                $userCheckerService->checkPostAuth($currentUser);
            } catch (AuthenticationException $e) {
                /** @var Response $logoutResponse */
                $logoutResponse = $this->security->logout(false);
                $requestEvent->setResponse($logoutResponse);
                $requestEvent->stopPropagation();
                return;
            }

            // refresh remember-me cookie
            if (!$this->security->isGranted('IS_IMPERSONATOR')) {
                $this->rememberMeHandler->createRememberMeCookie($currentUser);
            }
        }
    }

    public function onKernelController(ControllerEvent $controllerEvent): void
    {
        $firewallName = $this->security->getFirewallConfig($controllerEvent->getRequest())?->getName();

        // Check if the event should be handled for this firewall
        if (!in_array($firewallName, self::FIREWALLS)) {
            return;
        }

        $controller = $controllerEvent->getController();
        // is returned as [$controllerInstance, 'methodName']
        if (is_array($controller)) {
            $controller = $controller[0];
        }

        $userAuthenticatedRemembered = $this->security->isGranted('IS_AUTHENTICATED_REMEMBERED');
        $userAuthenticatedFully = $this->security->isGranted('IS_AUTHENTICATED_FULLY');

        $denyAccess = ($controller instanceof AbstractCPController && !$userAuthenticatedFully) ||
            (
                ($controller instanceof AbstractPZController) &&
                !($controller instanceof SecurityController) &&
                !($controller instanceof SignupController) &&
                !$userAuthenticatedRemembered &&
                !$userAuthenticatedFully
            );

        if ($denyAccess) {
            $request = $controllerEvent->getRequest();
            /** @var FlashBagAwareSessionInterface $session */
            $session = $request->getSession();
            $requestURI = $request->getRequestUri();
            $this->saveTargetPath($session, 'main', $requestURI);
            $errorMessage = 'You must log in to access this page.';
            $session->getFlashBag()->add('danger', $errorMessage);
            throw new AccessDeniedException($errorMessage);
        }
    }

    public function onLoginSuccess(LoginSuccessEvent $event): void
    {

        $firewallName = $event->getFirewallName();

        // Check if the event should be handled for this firewall
        if (!in_array($firewallName, self::FIREWALLS)) {
            return;
        }

        /** @var User $user */
        $user = $event->getUser();

        $authenticator = $event->getAuthenticator();

        $fromRememberMe = $authenticator instanceof RememberMeAuthenticator;

        $this->securityHelper->logUserLoginSuccess($event->getRequest(), $user, $fromRememberMe);

        // Log the successful login attempt
        $this->logger->info('User logged in successfully', [
            'email' => $user->getUserIdentifier(),
            'firewall' => $firewallName,
            'time' => (CarbonImmutable::now())->format(DATE_ATOM),
        ]);
    }

    public function onLoginFailure(LoginFailureEvent $event): void
    {
        $firewallName = $event->getFirewallName();

        if (!in_array($firewallName, self::FIREWALLS)) {
            return;
        }

        $authenticationException = $event->getException();
        $userIdentifier = $event->getPassport()?->getBadge(UserBadge::class)->getUserIdentifier();

        $this->securityHelper->logUserLoginFailed($event->getRequest(), $userIdentifier);

        // Log the failed login attempt
        $this->logger->warning('User failed to log in', [
            'email' => $userIdentifier,
            'firewall' => $firewallName,
            'time' => (CarbonImmutable::now())->format(DATE_ATOM),
            'error' => $authenticationException->getMessageData(),
        ]);
    }
}

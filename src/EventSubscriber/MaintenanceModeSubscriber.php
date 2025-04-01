<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Override;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Twig\Environment;

readonly class MaintenanceModeSubscriber implements EventSubscriberInterface
{
    private const array API_FIREWALLS = ['api1', 'api2', 'dockontrol_node_api'];

    public function __construct(
        #[Autowire('%env(bool:MAINTENANCE_MODE)%')]
        private bool $isMaintenance,
        private Environment $twig,
        private Security $security,
    ) {
    }

    /**
     * @return array<string,array{string,int}>
     */
    #[Override]
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 10],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$this->isMaintenance) {
            return;
        }

        $firewallName = $this->security->getFirewallConfig($event->getRequest())?->getName();

        $response = null;

        if (in_array($firewallName, self::API_FIREWALLS)) {
            $response = new JsonResponse(
                [
                    'status' => 'error',
                    'message' => 'The site is currently undergoing maintenance. Please try again later.',
                ],
                Response::HTTP_SERVICE_UNAVAILABLE
            );
        } else {
            $response = new Response(
                $this->twig->render('maintenance/maintenance_mode.html.twig'),
                Response::HTTP_SERVICE_UNAVAILABLE,
            );
        }

        $event->setResponse($response);
        $event->stopPropagation();
    }
}

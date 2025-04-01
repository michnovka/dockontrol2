<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\Guest;
use App\Event\LocaleEvent;
use Override;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Translation\LocaleSwitcher;

class LocaleListenerEventSubscriber implements EventSubscriberInterface
{
    public function __construct(private TokenStorageInterface $tokenStorage, private LocaleSwitcher $localeSwitcher)
    {
    }

    /**
     * @return array<string,array{string,int}>
     */
    #[Override]
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 2],
            LocaleEvent::class => ['onChangeLocale', 2],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $cookies = $request->cookies;
        $currentUser = $this->tokenStorage->getToken()?->getUser();
        $defaultLocale = 'cs';
        if ($currentUser instanceof Guest) {
            $defaultLocale = $currentUser->getDefaultLanguage();
        }
        if ($cookies->has('locale') && !empty($cookies->get('locale'))) {
            $locale = (string) $cookies->get('locale');
            $request->setLocale((string) $cookies->get('locale'));
            $this->localeSwitcher->setLocale($locale);
        } else {
            $request->setLocale($defaultLocale);
            $this->localeSwitcher->setLocale($defaultLocale);
        }
    }

    public function onChangeLocale(LocaleEvent $event): void
    {
        $request = $event->getRequest();
        $locale = $request->getLocale();
        $request->setLocale($locale);
        $this->localeSwitcher->setLocale($locale);
    }
}

<?php

declare(strict_types=1);

namespace App\Security\Authenticator;

use App\Entity\Guest;
use Override;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\CustomCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class GuestAuthenticator extends AbstractAuthenticator
{
    public function __construct(private readonly Environment $twig, private readonly TranslatorInterface $translator)
    {
    }

    #[Override]
    public function supports(Request $request): ?bool
    {
        return $request->attributes->has('hash');
    }

    #[Override]
    public function authenticate(Request $request): Passport
    {
        $hash = $request->attributes->getString('hash');
        $isValidHash = Uuid::isValid($hash);

        if (!$isValidHash) {
            throw new AuthenticationException($this->translator->trans('dockontrol.error.guest_401.invalid_hash'));
        }

        return new Passport(
            new UserBadge($hash),
            new CustomCredentials(
                function (string $hash, Guest $guest): bool {
                    return $guest->getHash()->toString() === $hash;
                },
                $hash
            )
        );
    }

    #[Override]
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    #[Override]
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $exception = $exception->getPrevious() ?? $exception;
        $htmlContent = $this->twig->render('@Twig/Exception/guest_error401.html.twig', [
            'message' => $exception->getMessage(),
            'isGuest' => true,
        ]);

        return new Response($htmlContent, Response::HTTP_UNAUTHORIZED);
    }
}

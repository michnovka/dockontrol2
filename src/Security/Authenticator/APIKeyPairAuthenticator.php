<?php

declare(strict_types=1);

namespace App\Security\Authenticator;

use App\Security\Credentials\APIKeyPairCredentials;
use Override;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;

class APIKeyPairAuthenticator extends AbstractAuthenticator
{
    #[Override]
    public function supports(Request $request): ?bool
    {
        return $request->headers->has('X-API-KEY') && $request->headers->has('X-API-SIGNATURE');
    }

    #[Override]
    public function authenticate(Request $request): Passport
    {
        $pubkey = (string) $request->headers->get('X-API-KEY');
        $signature = (string) $request->headers->get('X-API-SIGNATURE');
        $timestamp = (int) $request->headers->get('X-API-TIMESTAMP');

        $userBadge = new UserBadge($pubkey);

        $credentials = new APIKeyPairCredentials(
            $signature,
            $timestamp,
            $request->getMethod(),
            $request->getPathInfo(),
            $request->getContent()
        );

        return new Passport($userBadge, $credentials);
    }

    #[Override]
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    #[Override]
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse(['message' => strtr($exception->getMessageKey(), $exception->getMessageData())], Response::HTTP_UNAUTHORIZED);
    }
}

<?php

declare(strict_types=1);

namespace App\Security\Authenticator;

use Override;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;

class LegacyAPIAuthenticator extends AbstractAuthenticator
{
    #[Override]
    public function supports(Request $request): bool
    {
        return array_all(['username', 'password', 'action'], fn ($key) => $request->request->has($key) || $request->query->has($key));
    }

    #[Override]
    public function authenticate(Request $request): Passport
    {
        $username = (string) $this->getRequestValue($request, 'username');
        $password = (string) $this->getRequestValue($request, 'password');

        return new Passport(
            new UserBadge($username),
            new PasswordCredentials($password)
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
        return new JsonResponse([
            'status' => 'error',
            'message' => strtr($exception->getMessageKey(), $exception->getMessageData()),
        ], Response::HTTP_UNAUTHORIZED);
    }

    public function getRequestValue(Request $request, string $key): mixed
    {
        $value = null;
        if ($request->query->has($key)) {
            $value = $request->query->get($key);
        } elseif ($request->request->has($key)) {
            $value = $request->request->get($key);
        }

        return $value;
    }
}

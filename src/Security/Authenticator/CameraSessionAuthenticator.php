<?php

declare(strict_types=1);

namespace App\Security\Authenticator;

use App\Security\Credentials\CameraSessionCredentials;
use Override;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;

class CameraSessionAuthenticator extends AbstractAuthenticator
{
    #[Override]
    public function supports(Request $request): ?bool
    {
        return $request->attributes->get('_route') === 'dockontrol_camera_view';
    }

    #[Override]
    public function authenticate(Request $request): Passport
    {
        $cameraSessionId = $request->attributes->get('cameraSessionId');
        $cameraId = $request->attributes->get('cameraId');

        $userBadge = new UserBadge($cameraSessionId);
        $credentials = new CameraSessionCredentials($cameraId);

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
        return null;
    }
}

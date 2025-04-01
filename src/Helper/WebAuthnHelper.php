<?php

declare(strict_types=1);

namespace App\Helper;

use App\Entity\User;
use App\Entity\WebauthnRegistration;
use App\Repository\WebauthnRegistrationRepository;
use Carbon\CarbonImmutable;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use lbuchs\WebAuthn\WebAuthn;
use lbuchs\WebAuthn\WebAuthnException;
use RuntimeException;
use stdClass;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Throwable;

readonly class WebAuthnHelper
{
    public function __construct(
        private RequestStack $requestStack,
        private EntityManagerInterface $entityManager,
        private WebauthnRegistrationRepository $webauthnRegistrationRepository,
        private ParameterBagInterface $parameterBag,
    ) {
    }

    /**
     * @throws WebAuthnException
     */
    public function createArgsAndStoreChallengeIntoSession(
        string $userId,
        string $userIdentifier,
        string $userDisplayName,
        int $timout,
    ): StdClass {
        $webauthn = $this->getWebAuthn();
        $createdArgs = $webauthn->getCreateArgs($userId, $userIdentifier, $userDisplayName, $timout);
        $this->requestStack->getSession()->set('webauthn_challenge', $webauthn->getChallenge());

        return $createdArgs;
    }

    public function processCreateRequest(
        string $clientDataJSON,
        string $attestationObject,
        User $user,
    ): void {
        try {
            $webAuthn = $this->getWebAuthn();
            $challenge = $this->requestStack->getSession()->get('webauthn_challenge');

            if (!$challenge) {
                throw new InvalidArgumentException("Challenge not found in session.");
            }

            $data = $webAuthn->processCreate($clientDataJSON, $attestationObject, $challenge);

            $webauthnRegistration = new WebauthnRegistration();
            $webauthnRegistration->setUser($user);
            $webauthnRegistration->setLastUsedTime(CarbonImmutable::now());
            $webauthnRegistration->setCreatedTime(CarbonImmutable::now());
            $webauthnRegistration->setData(serialize($data));
            $webauthnRegistration->setCredentialId(bin2hex($data->credentialId));

            $this->entityManager->persist($webauthnRegistration);
            $this->entityManager->flush();
        } catch (Throwable $e) {
            throw new RuntimeException('Failed process create request, ' . $e->getMessage());
        }
    }

    public function getArgsForUser(User $currentUser): StdClass
    {
        try {
            $userCredentials = $this->webauthnRegistrationRepository->getCredentialsForUser($currentUser);
            $ids = [];
            foreach ($userCredentials as $userCredential) {
                $ids[] = hex2bin($userCredential);
            }
            if (empty($ids)) {
                throw new RuntimeException('no registrations in session.');
            }
            $webAuthn = $this->getWebAuthn();
            $getArgs = $webAuthn->getGetArgs($ids);
            $this->requestStack->getSession()->set('webauthn_challenge', $webAuthn->getChallenge());
            return $getArgs;
        } catch (Throwable $e) {
            throw new RuntimeException($e->getMessage());
        }
    }

    public function processGetRequest(
        string $clientDataJSON,
        string $authenticatorData,
        string $signature,
        string $credentialPublicKey,
        WebauthnRegistration $webauthnRegistration,
    ): void {
        try {
            $challenge = $this->requestStack->getSession()->get('webauthn_challenge');

            if (!$challenge) {
                throw new InvalidArgumentException("Challenge not found in session.");
            }

            $webAuthn = $this->getWebAuthn();
            $webAuthn->processGet($clientDataJSON, $authenticatorData, $signature, $credentialPublicKey, $challenge);
            $webauthnRegistration->setLastUsedTime(CarbonImmutable::now());
        } catch (Throwable $e) {
            throw new RuntimeException('Failed process get request, ' . $e->getMessage());
        }
    }

    /**
     * @throws WebAuthnException
     */
    private function getWebAuthn(): WebAuthn
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            throw new RuntimeException('Invalid Request');
        }
        $domainOverride = $this->parameterBag->get('webauthn_domain_override');
        /** @var string $rpId*/
        $rpId = !empty($domainOverride) ? $domainOverride : $request->getHttpHost();
        $formats = ['android-key', 'android-safetynet', 'fido-u2f', 'none', 'packed'];
        return new WebAuthn('DOCKontrol WebAuthn Library', $rpId, $formats);
    }
}

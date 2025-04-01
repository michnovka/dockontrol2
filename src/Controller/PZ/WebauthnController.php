<?php

declare(strict_types=1);

namespace App\Controller\PZ;

use App\Entity\User;
use App\Helper\WebAuthnHelper;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;

#[Route('/webauthn')]
#[IsGranted('ROLE_TENANT')]
class WebauthnController extends AbstractPZController
{
    public function __construct(
        private readonly WebAuthnHelper $webAuthnHelper,
    ) {
    }

    #[Route('/create-args', name: 'dockontrol_webauthn_create_args')]
    public function createArgs(): JsonResponse
    {
        /** @var User $currentUser*/
        $currentUser = $this->getUser();
        try {
            $createdArgs = $this->webAuthnHelper->createArgsAndStoreChallengeIntoSession((string) $currentUser->getId(), $currentUser->getEmail(), $currentUser->getName(), 30);
        } catch (Throwable $e) {
            return $this->json([
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        return $this->json([
            'status' => true,
            'createdArgs' => $createdArgs,
        ]);
    }

    #[Route('/get-args', name: 'dockontrol_webauthn_get_args')]
    public function getArgs(): JsonResponse
    {
        /** @var User $currentUser*/
        $currentUser = $this->getUser();

        try {
            $getArgs = $this->webAuthnHelper->getArgsForUser($currentUser);
        } catch (Throwable $e) {
            return $this->json([
                'success' => false,
                'status' => 'error',
                'message' => $e->getMessage(),
            ]);
        }
        return $this->json([
            'success' => true,
            'status' => 'ok',
            'getArgs' => $getArgs,
        ]);
    }

    #[Route('/process-create', name: 'dockontrol_webauthn_process_create', methods: ['POST'])]
    public function processCreate(Request $request): JsonResponse
    {
        $success = false;
        $errorMessage = null;

        /** @var User $currentUser */
        $currentUser = $this->getUser();

        try {
            $bodyData = json_decode($request->getContent(), true);

            if (!isset($bodyData['clientDataJSON']) || !isset($bodyData['attestationObject'])) {
                throw new InvalidArgumentException("Missing WebAuthn credential data.");
            }

            $clientDataJSON = base64_decode($bodyData['clientDataJSON']);
            $attestationObject = base64_decode($bodyData['attestationObject']);

            $this->webAuthnHelper->processCreateRequest($clientDataJSON, $attestationObject, $currentUser);
            $success = true;
        } catch (Throwable $e) {
            $errorMessage = $e->getMessage();
        }

        return $this->json([
            'success' => $success,
            'errorMessage' => $errorMessage,
        ]);
    }
}

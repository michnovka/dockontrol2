<?php

declare(strict_types=1);

namespace App\Controller\CP\AccessManagement;

use App\Controller\CP\AbstractCPController;
use App\Entity\APIKey;
use App\Entity\Enum\UserRole;
use App\Entity\User;
use App\Form\APIKeyType;
use App\Form\Filter\ApiKeyFilterType;
use App\Helper\APIKeyHelper;
use App\Helper\UserActionLogHelper;
use App\Repository\APIKeyRepository;
use App\Security\Expression\RoleRequired;
use App\Security\Voter\APIKeyVoter;
use Knp\Component\Pager\PaginatorInterface;
use RuntimeException;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;

/** @psalm-import-type ApiKeyFilterArray from APIKeyRepository */
#[Route('/access-management/api-keys')]
class APIKeyController extends AbstractCPController
{
    public function __construct(
        private readonly APIKeyRepository $apiKeyRepository,
        private readonly APIKeyHelper $apiKeyHelper,
        private readonly UserActionLogHelper $userActionLogHelper,
    ) {
    }

    #[Route('/', name: 'cp_access_management_api_keys')]
    #[IsGranted(new RoleRequired(UserRole::ADMIN))]
    public function index(Request $request, PaginatorInterface $paginator): Response
    {
        $numberOfRecords = $request->query->getInt('numberOfRecords', 25);
        /** @var User $adminUser*/
        $adminUser = $this->getUser();

        $apiKeyFilterForm = $this->createForm(ApiKeyFilterType::class);
        $apiKeyFilterForm->handleRequest($request);
        $filter = [];

        if ($apiKeyFilterForm->isSubmitted() && $apiKeyFilterForm->isValid()) {
            $filter['user'] = $apiKeyFilterForm->get('user')->getData();
            $filter['timeCreated'] = $apiKeyFilterForm->get('timeCreated')->getData();
            $filter['timeLastUsed'] = $apiKeyFilterForm->get('timeLastUsed')->getData();
        }

        /** @psalm-var ApiKeyFilterArray $filter*/
        $queryBuilder = $this->apiKeyRepository->getQueryBuilder($adminUser, $filter);
        $apiKeys = $paginator->paginate($queryBuilder, $request->query->getInt('page', 1), $numberOfRecords);
        $showPrivateKeyFirstTime = $request->getSession()->get('SHOW_PRIVATE_KEY_FIRST_TIME', false);
        $publicKeyHash = $request->getSession()->get('API_KEY_PUBLIC_HASH');
        $apiKeyFromSession = null;

        if ($showPrivateKeyFirstTime) {
            $apiKeyFromSession = $this->apiKeyRepository->find($publicKeyHash);
            $request->getSession()->remove('SHOW_PRIVATE_KEY_FIRST_TIME');
            $request->getSession()->remove('API_KEY_PUBLIC_HASH');
        }

        return $this->render('cp/access_management/api_key/index.html.twig', [
            'numberOfRecords' => $numberOfRecords,
            'apiKeys' => $apiKeys,
            'apiKeyFromSession' => $apiKeyFromSession,
            'showPrivateKeyFirstTime' => $showPrivateKeyFirstTime,
            'apiKeyFilterForm' => $apiKeyFilterForm->createView(),
        ]);
    }

    #[Route('/new', name: 'cp_access_management_api_keys_new')]
    #[IsGranted(new RoleRequired(UserRole::ADMIN))]
    public function new(Request $request): Response
    {
        $apiKey = new APIKey();
        $form = $this->createForm(APIKeyType::class, $apiKey);
        $form->handleRequest($request);
        /** @var User $user*/
        $user = $this->getUser();

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                if (!$this->isGranted(APIKeyVoter::CREATE, $apiKey)) {
                    throw new RuntimeException('You don\'t have permission to create API key.');
                }
                $this->apiKeyHelper->saveAPIKey($apiKey);
                $this->userActionLogHelper->addUserActionLog('Created API key ' . $apiKey->getPublicKey()->toString() . ' for ' . $apiKey->getUser()->getEmail(), $user);
                $request->getSession()->set('SHOW_PRIVATE_KEY_FIRST_TIME', true);
                $request->getSession()->set('API_KEY_PUBLIC_HASH', $apiKey->getPublicKey());
                $this->addFlash('success', 'Successfully created new API key.');
            } catch (Throwable $exception) {
                $this->addFlash('danger', 'Failed to create new API key. ' . $exception->getMessage());
            }

            return $this->redirectToRoute('cp_access_management_api_keys');
        }

        return $this->render('cp/access_management/api_key/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{publicKey}/delete', name: 'cp_access_management_api_keys_delete')]
    #[IsGranted(APIKeyVoter::DELETE, 'apiKey')]
    public function delete(Request $request, #[MapEntity(id: 'publicKey')] APIKey $apiKey): JsonResponse
    {
        $errorMessage = null;
        $status = false;
        $csrfToken = $request->request->getString('_csrf');
        /** @var User $user */
        $user = $this->getUser();

        if (!$this->isCsrfTokenValid('apikeycsrf', $csrfToken)) {
            $errorMessage = 'Invalid CSRF token.';
        } else {
            try {
                $this->userActionLogHelper->addUserActionLog('Deleted API key ' . $apiKey->getPublicKey()->toString(), $user);
                $this->apiKeyHelper->deleteAPIKey($apiKey);
                $status = true;
                $this->addFlash('danger', 'API key deleted successfully.');
            } catch (Throwable $exception) {
                $errorMessage = 'Failed to delete API key' . $exception->getMessage();
            }
        }

        return $this->json(['status' => $status, 'errorMessage' => $errorMessage]);
    }
}

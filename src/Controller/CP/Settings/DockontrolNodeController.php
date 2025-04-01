<?php

declare(strict_types=1);

namespace App\Controller\CP\Settings;

use App\Controller\CP\AbstractCPController;
use App\Entity\DockontrolNode;
use App\Entity\User;
use App\Form\DockontrolNodeManageUserType;
use App\Form\DockontrolNodeType;
use App\Helper\DockontrolNodeHelper;
use App\Helper\UserActionLogHelper;
use App\Repository\DockontrolNodeRepository;
use App\Security\Voter\DockontrolNodeVoter;
use InvalidArgumentException;
use Knp\Component\Pager\PaginatorInterface;
use RuntimeException;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;

#[Route('/setting/dockontrol-node')]
class DockontrolNodeController extends AbstractCPController
{
    public function __construct(
        private readonly DockontrolNodeRepository $dockontrolNodeRepository,
        private readonly DockontrolNodeHelper $dockontrolNodeHelper,
        private readonly UserActionLogHelper $userActionLogHelper,
    ) {
    }

    #[Route('/', name: 'cp_settings_node')]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function index(Request $request, PaginatorInterface $paginator): Response
    {
        $numberOfRecords = $request->query->getInt('numberOfRecords', 25);

        $queryBuilder = $this->dockontrolNodeRepository->getQueryBuilder();
        $dockontrolNodes = $paginator->paginate($queryBuilder, $request->query->getInt('page', 1), $numberOfRecords, [
            'defaultSortFieldName' => 'n.name',
            'defaultSortDirection' => 'asc',
        ]);

        return $this->render('cp/settings/dockontrol_node/index.html.twig', [
            'nodes' => $dockontrolNodes,
            'numberOfRecords' => $numberOfRecords,
        ]);
    }

    #[Route('/new', name: 'cp_settings_node_new')]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function new(Request $request): Response
    {
        /** @var User $adminUser*/
        $adminUser = $this->getUser();
        $dockontrolNode = new DockontrolNode();
        $this->dockontrolNodeHelper->populateNewWireguardKeyPair($dockontrolNode);

        $form = $this->createForm(DockontrolNodeType::class, $dockontrolNode);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                if (!$this->isGranted(DockontrolNodeVoter::CREATE, $dockontrolNode)) {
                    throw new RuntimeException('You don\'t have permission to create dockontrol node.');
                }
                $this->dockontrolNodeHelper->saveDockontrolNode($dockontrolNode, false);
                $this->userActionLogHelper->addUserActionLog('Created dockontrol node #' . $dockontrolNode->getId() . ' (' . $dockontrolNode->getName() . ')', $adminUser);
                $this->addFlash('success', 'Node created successfully.');
            } catch (Throwable $throwable) {
                $this->addFlash('danger', 'Failed to create node ' . $throwable->getMessage());
            }

            $request->getSession()->set('SHOW_KEY_PAIR_FIRST_TIME', true);
            return $this->redirectToRoute('cp_settings_node_edit', ['id' => $dockontrolNode->getId()]);
        }
        return $this->render('cp/settings/dockontrol_node/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'cp_settings_node_edit')]
    #[IsGranted(DockontrolNodeVoter::EDIT, 'dockontrolNode')]
    public function edit(Request $request, #[MapEntity(id: 'id')] DockontrolNode $dockontrolNode): Response
    {
        /** @var User $adminUser*/
        $adminUser = $this->getUser();
        $usersToNotifyWhenStatusChanges = $dockontrolNode->getUsersToNotifyWhenStatusChanges()->getValues();

        $form = $this->createForm(DockontrolNodeType::class, $dockontrolNode, [
            'editable' => true,
        ]);
        $form->handleRequest($request);

        $manageUserForm = $this->createForm(DockontrolNodeManageUserType::class, $dockontrolNode);
        $manageUserForm->handleRequest($request);

        $showKeyPairFirstTime = $request->getSession()->get('SHOW_KEY_PAIR_FIRST_TIME');

        if ($showKeyPairFirstTime) {
            $request->getSession()->remove('SHOW_KEY_PAIR_FIRST_TIME');
        }

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                if (!$this->isGranted(DockontrolNodeVoter::EDIT, $dockontrolNode)) {
                    throw new RuntimeException('You don\'t have permission to edit dockontrol node.');
                }
                $this->dockontrolNodeHelper->saveDockontrolNode($dockontrolNode, false);
                $this->userActionLogHelper->addUserActionLog('Updated dockontrol node #' . $dockontrolNode->getId() . ' (' . $dockontrolNode->getName() . ')', $adminUser);
                $this->addFlash('success', 'Node updated successfully.');
            } catch (Throwable $throwable) {
                $this->addFlash('danger', 'Failed to update node ' . $throwable->getMessage());
            }

            return $this->redirectToRoute('cp_settings_node');
        }

        if ($manageUserForm->isSubmitted()) {
            try {
                if (!$this->isGranted(DockontrolNodeVoter::EDIT, $dockontrolNode)) {
                    throw new RuntimeException('You don\'t have permission to edit dockontrol node.');
                }
                $updatedUsers = $manageUserForm->get('usersToNotifyWhenStatusChanges')->getData()->getValues();
                $this->dockontrolNodeHelper->updateUsersToNotifyWhenStatusChange($dockontrolNode, $usersToNotifyWhenStatusChanges, $updatedUsers, $adminUser);
                $this->addFlash('success', 'Node updated successfully.');
            } catch (Throwable $throwable) {
                $this->addFlash('danger', 'Failed to update users, ' . $throwable->getMessage());
            }
            return $this->redirectToRoute('cp_settings_node');
        }
        return $this->render('cp/settings/dockontrol_node/edit.html.twig', [
            'form' => $form->createView(),
            'node' => $dockontrolNode,
            'showKeyPairFirstTime' => $showKeyPairFirstTime,
            'manageUserForm' => $manageUserForm->createView(),
        ]);
    }

    #[Route('/{id}/delete', name: 'cp_settings_node_delete')]
    #[IsGranted(DockontrolNodeVoter::DELETE, 'dockontrolNode')]
    public function delete(Request $request, #[MapEntity(id: 'id')] DockontrolNode $dockontrolNode): JsonResponse
    {
        /** @var User $adminUser*/
        $adminUser = $this->getUser();

        $csrfToken = (string) $request->getPayload()->get('_csrf');
        $status = false;
        $errorMessage = null;

        if (!$this->isCsrfTokenValid('nodecsrf', $csrfToken)) {
            $errorMessage = 'Invalid CSRF token.';
        } else {
            try {
                $this->userActionLogHelper->addUserActionLog('Deleted dockontrol node #' . $dockontrolNode->getId() . ' (' . $dockontrolNode->getName() . ')', $adminUser);
                $this->dockontrolNodeHelper->removeDockontrolNode($dockontrolNode);
                $status = true;
                $this->addFlash('danger', 'Node deleted successfully.');
            } catch (Throwable $throwable) {
                $errorMessage = 'Failed to delete node ' . $throwable->getMessage();
            }
        }

        return $this->json(['status' => $status, 'errorMessage' => $errorMessage]);
    }

    #[Route('/{id}/regenerate-api-keys', name: 'cp_settings_regenerate_api_keys')]
    #[IsGranted(DockontrolNodeVoter::EDIT, 'dockontrolNode')]
    public function regenerateAPIKeys(
        Request $request,
        #[MapEntity(id: 'id')] DockontrolNode $dockontrolNode,
    ): JsonResponse {
        return $this->regenerateKeys($request, $dockontrolNode, 'api');
    }

    #[Route('/{id}/regenerate-wg-keys', name: 'cp_settings_regenerate_wg_keys')]
    #[IsGranted(DockontrolNodeVoter::EDIT, 'dockontrolNode')]
    public function regenerateWgKeys(
        Request $request,
        #[MapEntity(id: 'id')] DockontrolNode $dockontrolNode,
    ): JsonResponse {
        return $this->regenerateKeys($request, $dockontrolNode, 'wg');
    }

    private function regenerateKeys(Request $request, DockontrolNode $dockontrolNode, string $keyType): JsonResponse
    {
        /** @var User $adminUser */
        $adminUser = $this->getUser();
        $csrfToken = (string) $request->getPayload()->get('_csrf');
        $errorMessage = null;
        $status = false;
        $publicKey = null;
        $secretKey = null;

        if (!$this->isCsrfTokenValid('nodecsrf', $csrfToken)) {
            $errorMessage = 'Invalid CSRF token.';
        } else {
            try {
                if (!$this->isGranted(DockontrolNodeVoter::EDIT, $dockontrolNode)) {
                    throw new RuntimeException('You don\'t have permission to edit dockontrol node.');
                }

                if ($keyType === 'api') {
                    $oldPublicAPIKey = $dockontrolNode->getApiPublicKey();
                    $this->dockontrolNodeHelper->regenerateAPIKeys($dockontrolNode);
                    $publicKey = $dockontrolNode->getApiPublicKey();
                    $description = 'Regenerated API Keys for Node #' . $dockontrolNode->getId() . ' (Old API Public Key) ' . $oldPublicAPIKey->toString() . ' (New API Public Key) ' . $publicKey->toString();
                    $secretKey = $dockontrolNode->getApiSecretKey();
                } elseif ($keyType === 'wg') {
                    $oldPublicWgKey = $dockontrolNode->getWireguardPublicKey();
                    $this->dockontrolNodeHelper->populateNewWireguardKeyPair($dockontrolNode);
                    $publicKey = $dockontrolNode->getWireguardPublicKey();
                    $description = 'Regenerated WG keys for node #' . $dockontrolNode->getId() . ' (Old WG Public Key) ' . $oldPublicWgKey . ' (New WG Public Key) ' . $publicKey;
                    $secretKey = $dockontrolNode->getWireguardPrivateKey();
                } else {
                    throw new InvalidArgumentException('Invalid key type ' . $keyType);
                }

                $this->userActionLogHelper->addUserActionLog($description, $adminUser);
                $status = true;
            } catch (Throwable $throwable) {
                $errorMessage = 'Failed to regenerate ' . strtoupper($keyType) . ' keys ' . $throwable->getMessage();
            }
        }

        return $this->json([
            'success' => $status,
            'message' => $errorMessage,
            'publicKey' => $publicKey,
            'secretKey' => $secretKey,
        ]);
    }
}

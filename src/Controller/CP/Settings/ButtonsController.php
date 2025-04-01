<?php

declare(strict_types=1);

namespace App\Controller\CP\Settings;

use App\Controller\CP\AbstractCPController;
use App\Entity\Button;
use App\Entity\User;
use App\Form\ButtonType;
use App\Helper\ButtonHelper;
use App\Helper\UserActionLogHelper;
use App\Repository\ButtonRepository;
use App\Security\Voter\ButtonVoter;
use App\Twig\Extensions\ButtonRuntime;
use Knp\Component\Pager\PaginatorInterface;
use RuntimeException;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;

#[Route('/settings/button')]
class ButtonsController extends AbstractCPController
{
    public function __construct(
        private readonly ButtonRepository $buttonRepository,
        private readonly ButtonHelper $buttonHelper,
        private readonly UserActionLogHelper $userActionLogHelper,
    ) {
    }

    #[Route('/', name: 'cp_settings_button')]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function index(Request $request, PaginatorInterface $paginator): Response
    {
        $numberOfRecords = $request->query->getInt('numberOfRecords', 25);

        $queryBuilder = $this->buttonRepository->getQueryBuilder();
        $buttons = $paginator->paginate($queryBuilder, $request->query->getInt('page', 1), $numberOfRecords, [
            'defaultSortFieldName' => 'b.id',
            'defaultSortDirection' => 'asc',
        ]);

        return $this->render('cp/settings/button/index.html.twig', [
            'buttons' => $buttons,
            'numberOfRecords' => $numberOfRecords,
        ]);
    }

    #[Route('/new', name: 'cp_settings_button_new')]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function new(Request $request): Response
    {
        /** @var User $adminUser*/
        $adminUser = $this->getUser();

        $button = new Button();
        $form = $this->createForm(ButtonType::class, $button);
        $form->handleRequest($request);
        $validKeys = array_keys(ButtonRuntime::TRANSLATABLE_KEYS);
        $tooltipText = "You can use the following keys inside %%. Example: \"%elevator% is working\" will be translated.<br><br>";
        $tooltipText .= implode(", ", array_map(fn ($key) => "%{$key}%", $validKeys));

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                if (!$this->isGranted(ButtonVoter::CREATE, $button)) {
                    throw new RuntimeException('You don\'t have permission to create button.');
                }
                $this->buttonHelper->saveButton($button, true);
                $this->userActionLogHelper->addUserActionLog('Created button ' . $button->getId(), $adminUser);
                $this->addFlash('success', 'Button created successfully.');
            } catch (Throwable $throwable) {
                $this->addFlash('error', 'Failed to created button: ' . $throwable->getMessage());
            }

            return $this->redirectToRoute('cp_settings_button');
        }

        return $this->render('cp/settings/button/new.html.twig', [
            'form' => $form->createView(),
            'translation_help' => $tooltipText,
        ]);
    }

    #[Route('/{id}/edit', name: 'cp_settings_button_edit')]
    #[IsGranted(ButtonVoter::EDIT, 'button')]
    public function edit(Request $request, #[MapEntity(id: 'id')] Button $button): Response
    {
        /** @var User $adminUser*/
        $adminUser = $this->getUser();

        $form = $this->createForm(ButtonType::class, $button);
        $form->handleRequest($request);
        $validKeys = array_keys(ButtonRuntime::TRANSLATABLE_KEYS);
        $tooltipText = "You can use the following keys inside %%. Example: \"%elevator% is working\" will be translated.<br><br>";
        $tooltipText .= implode(", ", array_map(fn ($key) => "%{$key}%", $validKeys));

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                if (!$this->isGranted(ButtonVoter::EDIT, $button)) {
                    throw new RuntimeException('You don\'t have permission to edit button.');
                }

                $this->buttonHelper->saveButton($button);
                $this->userActionLogHelper->addUserActionLog('Updated button ' . $button->getId(), $adminUser);
                $this->addFlash('success', 'Button updated successfully.');
            } catch (Throwable $throwable) {
                $this->addFlash('error', 'Failed to update button: ' . $throwable->getMessage());
            }

            return $this->redirectToRoute('cp_settings_button');
        }

        return $this->render('cp/settings/button/edit.html.twig', [
            'form' => $form->createView(),
            'button' => $button,
            'translation_help' => $tooltipText,
        ]);
    }

    #[Route('/{id}/delete', name: 'cp_settings_button_delete')]
    #[IsGranted(ButtonVoter::DELETE, 'button')]
    public function delete(Request $request, #[MapEntity(id: 'id')] Button $button): JsonResponse
    {
        /** @var User $adminUser*/
        $adminUser = $this->getUser();

        $csrfToken = (string) $request->getPayload()->get('_csrf');
        $errorMessage = null;
        $status = false;
        if (!$this->isCsrfTokenValid('buttoncsrf', $csrfToken)) {
            $errorMessage = 'Invalid CSRF token.';
        } else {
            try {
                $this->userActionLogHelper->addUserActionLog('Deleted button ' . $button->getId(), $adminUser, false);
                $this->buttonHelper->removeButton($button);
                $status = true;
                $this->addFlash('danger', 'Button deleted successfully.');
            } catch (Throwable $throwable) {
                $errorMessage = 'Failed to delete Button ' . $throwable->getMessage();
            }
        }

        return $this->json(['status' => $status, 'errorMessage' => $errorMessage]);
    }

    #[Route('/check-button-is-exist', name: 'cp_settings_button_is_exist')]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function checkButtonIsExist(Request $request): JsonResponse
    {
        $buttonId =  trim((string) $request->getPayload()->get('buttonId'));
        $button = $this->buttonRepository->find($buttonId);
        return $this->json(['buttonExist' => $button instanceof Button]);
    }
}

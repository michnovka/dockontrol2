<?php

declare(strict_types=1);

namespace App\Controller\PZ\Settings;

use App\Controller\PZ\AbstractPZController;
use App\Entity\Nuki;
use App\Entity\User;
use App\Exception\Nuki\Password1Mismatch;
use App\Exception\Nuki\PINMismatch;
use App\Exception\Nuki\TooManyTries;
use App\Form\NukiPublicSettingsType;
use App\Helper\NukiHelper;
use App\Helper\UserActionLogHelper;
use App\Security\Voter\NukiVoter;
use RuntimeException;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;

#[Route('/nuki')]
#[IsGranted('ROLE_TENANT')]
class NukiController extends AbstractPZController
{
    public function __construct(
        private readonly NukiHelper $nukiHelper,
        private readonly TranslatorInterface $translator,
        private readonly UserActionLogHelper $userActionLogHelper,
    ) {
    }

    #[Route('/new', name: 'dockontrol_nuki_new')]
    public function index(Request $request): Response
    {
        $nuki = new Nuki();
        /** @var User $user*/
        $user = $this->getUser();
        $nuki->setUser($user);
        $nukiTypeForm = $this->createForm(NukiPublicSettingsType::class, $nuki);
        $nukiTypeForm->handleRequest($request);
        if ($nukiTypeForm->isSubmitted() && $nukiTypeForm->isValid()) {
            try {
                if (!$this->isGranted(NukiVoter::CREATE, $nuki)) {
                    throw new RuntimeException($this->translator->trans('dockontrol.settings.nuki.messages.nuki_created_not_allowed_message'));
                }
                $this->nukiHelper->saveNuki($nuki, $nukiTypeForm->get('password1')->getData());
                $description = 'Created NUKI #' . $nuki->getId() . ' (' . $nuki->getName() . ')';
                $this->userActionLogHelper->addUserActionLog($description, $user);
                $this->addFlash('success', $this->translator->trans('dockontrol.settings.nuki.messages.nuki_created_message'));
            } catch (Throwable $throwable) {
                $this->addFlash('danger', $this->translator->trans('dockontrol.settings.nuki.messages.failed_to_add_nuki') . $throwable->getMessage());
            }

            return $this->redirectToRoute('dockontrol_settings_nuki');
        }
        return $this->render('pz/settings/nuki/new.html.twig', [
            'nukiTypeForm' => $nukiTypeForm->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'dockontrol_nuki_edit')]
    #[IsGranted(NukiVoter::EDIT, 'nuki')]
    public function edit(Request $request, #[MapEntity(id: 'id')] Nuki $nuki): Response
    {
        /** @var User $user*/
        $user = $this->getUser();
        $nukiTypeForm = $this->createForm(NukiPublicSettingsType::class, $nuki, [
            'required_password' => false,
        ]);
        $nukiTypeForm->handleRequest($request);
        if ($nukiTypeForm->isSubmitted() && $nukiTypeForm->isValid()) {
            try {
                if (!$this->isGranted(NukiVoter::EDIT, $nuki)) {
                    throw new RuntimeException($this->translator->trans('dockontrol.settings.nuki.messages.nuki_updated_not_allowed_message'));
                }

                $password1 = $nukiTypeForm->get('password1')->getData();
                $this->nukiHelper->saveNuki($nuki, $password1);
                $description = 'Updated NUKI #' . $nuki->getId() . ' (' . $nuki->getName() . ')';
                $this->userActionLogHelper->addUserActionLog($description, $user);
                $this->addFlash('success', $this->translator->trans('dockontrol.settings.nuki.messages.nuki_updated_message'));
            } catch (Throwable $throwable) {
                $this->addFlash('danger', $this->translator->trans('dockontrol.settings.nuki.messages.failed_to_update_nuki') . $throwable->getMessage());
            }

            return $this->redirectToRoute('dockontrol_settings_nuki');
        }

        return $this->render('pz/settings/nuki/edit.html.twig', [
            'nuki' => $nuki,
            'nukiTypeForm' => $nukiTypeForm->createView(),
        ]);
    }

    #[Route('/{id}/add-pin', name: 'dockontrol_nuki_add_pin')]
    #[IsGranted(NukiVoter::EDIT, 'nuki')]
    public function addPin(Request $request, #[MapEntity(id: 'id')] Nuki $nuki): JsonResponse
    {
        /** @var User $user*/
        $user = $this->getUser();
        $success = false;
        $csrfToken = (string) $request->request->get('_csrf');
        $pin = $request->request->getString('pin');
        $password1 = $request->request->getString('password1');
        $isPinRemoved = false;

        if (!$this->isCsrfTokenValid('nukicsrf', $csrfToken)) {
            $message = $this->translator->trans('dockontrol.global.invalid_csrf_token');
        } else {
            try {
                if (!$this->isGranted(NukiVoter::EDIT, $nuki)) {
                    throw new RuntimeException($this->translator->trans('dockontrol.settings.nuki.messages.nuki_updated_not_allowed_message'));
                }
                $isPinRemoved = empty($pin);
                $this->nukiHelper->checkPassword1($nuki, $password1);
                $this->nukiHelper->setPin($nuki, $isPinRemoved ? null : $pin);
                $description = 'Updated NUKI PIN for #' . $nuki->getId() . ' (' . $nuki->getName() . ')';
                $this->userActionLogHelper->addUserActionLog($description, $user);
                $success = true;
                $message = $this->translator->trans('dockontrol.settings.nuki.messages.configure_pin_saved_message');
            } catch (Password1Mismatch) {
                $message = $this->translator->trans('dockontrol.settings.nuki.messages.password1_is_invalid');
            } catch (TooManyTries) {
                $message = $this->translator->trans('dockontrol.settings.nuki.messages.too_many_tries');
            } catch (Throwable $throwable) {
                $message = $this->translator->trans('dockontrol.settings.nuki.messages.failed_to_edit_pin') . $throwable->getMessage();
            }
        }

        return $this->json(['success' => $success, 'message' => $message, 'isPinRemoved' => $isPinRemoved]);
    }

    #[Route('/{id}/delete', name: 'dockontrol_nuki_delete')]
    #[IsGranted(NukiVoter::DELETE, 'nuki')]
    public function delete(Request $request, #[MapEntity(id: 'id')] Nuki $nuki): JsonResponse
    {
        /** @var User $user*/
        $user = $this->getUser();
        $success = false;
        $csrfToken = (string) $request->request->get('_csrf');
        if (!$this->isCsrfTokenValid('nukicsrf', $csrfToken)) {
            $message = $this->translator->trans('dockontrol.global.invalid_csrf_token');
        } else {
            try {
                $description = 'Deleted NUKI #' . $nuki->getId() . ' (' . $nuki->getName() . ')';
                $this->userActionLogHelper->addUserActionLog($description, $user);
                $this->nukiHelper->deleteNuki($nuki);
                $success = true;
                $message = $this->translator->trans('dockontrol.settings.nuki.messages.nuki_removed_message');
            } catch (Throwable $throwable) {
                $message = $this->translator->trans('dockontrol.settings.nuki.messages.failed_to_remove_nuki') . $throwable->getMessage();
            }
        }

        return $this->json(['success' => $success, 'message' => $message]);
    }

    #[Route('/{id}/check-pin', name: 'dockontrol_nuki_check_pin', methods: ['POST'])]
    public function checkPin(Request $request, Nuki $nuki): JsonResponse
    {
        $pin = $request->request->getString('pin');
        $csrfToken = $request->request->getString('_csrf');
        $status = false;
        $errorMessage = null;

        if (!$this->isCsrfTokenValid('nukicsrf', $csrfToken)) {
            $errorMessage = $this->translator->trans('dockontrol.global.invalid_csrf_token');
        } else {
            try {
                $this->nukiHelper->checkEnteredPinIsValid($nuki, $pin);
                $status = true;
            } catch (PINMismatch) {
                $errorMessage = $this->translator->trans('dockontrol.settings.nuki.messages.incorrect_pin');
            } catch (TooManyTries) {
                $errorMessage = $this->translator->trans('dockontrol.settings.nuki.messages.too_many_tries');
            }
        }

        return $this->json([
            'status' => $status,
            'message' => $errorMessage,
        ]);
    }
}

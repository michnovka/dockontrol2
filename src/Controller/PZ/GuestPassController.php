<?php

declare(strict_types=1);

namespace App\Controller\PZ;

use App\Entity\Guest;
use App\Entity\User;
use App\Form\GuestPassType;
use App\Helper\GuestHelper;
use App\Helper\UserActionLogHelper;
use App\Security\Voter\GuestVoter;
use RuntimeException;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;

#[Route('/guest-pass')]
#[IsGranted('ROLE_TENANT')]
class GuestPassController extends AbstractPZController
{
    public function __construct(
        private readonly GuestHelper $guestHelper,
        private readonly TranslatorInterface $translator,
        private readonly UserActionLogHelper $userActionLogHelper,
    ) {
    }

    #[Route('/', name: 'dockontrol_guest_pass')]
    public function index(Request $request): Response
    {
        /** @var User $user*/
        $user = $this->getUser();
        $guestPasses = $this->guestHelper->getGuestPassForUser($user);
        $session = $request->getSession();
        $newGuestPassCreated = $session->get('new_guest_pass_created');
        $newGuestPass = $session->get('new_guest_pass');

        if ($newGuestPassCreated && !empty($newGuestPass)) {
            $session->remove('new_guest_pass_created');
            $session->remove('new_guest_pass');
        }

        return $this->render('pz/guest_pass/index.html.twig', [
            'guestPasses' => $guestPasses,
            'newGuestPassCreated' => $newGuestPassCreated,
            'newGuestPass' => $newGuestPass,
        ]);
    }

    #[Route('/new', name: 'dockontrol_guest_pass_create')]
    public function new(Request $request): Response
    {
        /** @var User $user*/
        $user = $this->getUser();
        if (!$user->isCanCreateGuests()) {
            $this->addFlash('danger', $this->translator->trans('dockontrol.guest_pass.messages.now_allowed_create_guest_pass'));
            return $this->redirectToRoute('dockontrol_main');
        }
        $guest = new Guest();
        $guest->setUser($user);
        $guestPassTypeForm = $this->createForm(GuestPassType::class, $guest);
        $guestPassTypeForm->handleRequest($request);
        if ($guestPassTypeForm->isSubmitted() && $guestPassTypeForm->isValid()) {
            try {
                if (!$this->isGranted(GuestVoter::CREATE, $guest)) {
                    throw new RuntimeException($this->translator->trans('dockontrol.guest_pass.messages.now_allowed_create_guest_pass'));
                }
                $intervalInHours = $guestPassTypeForm->get('expires')->getData();
                $this->guestHelper->createGuestPass($guest, $intervalInHours);
                $request->getSession()->set('new_guest_pass_created', true);
                $request->getSession()->set('new_guest_pass', $guest->getHash());
                $this->userActionLogHelper->addUserActionLog('Created guest pass ' . $guest->getHash()->toString(), $user);
//                $this->addFlash('success', 'Guest hash has been created.');
            } catch (Throwable $e) {
                $this->addFlash('danger', $this->translator->trans('dockontrol.guest_pass.messages.failed_to_create_guest_hash') . $e->getMessage());
            }

            return $this->redirectToRoute('dockontrol_guest_pass');
        }
        return $this->render('pz/guest_pass/new.html.twig', [
            'form' => $guestPassTypeForm->createView(),
        ]);
    }

    #[Route('/{id}/delete', name: 'dockontrol_guest_pass_delete')]
    #[IsGranted(GuestVoter::DELETE, 'guest')]
    public function delete(Request $request, #[MapEntity(id: 'id')] Guest $guest): JsonResponse
    {
        /** @var User $user*/
        $user = $this->getUser();
        $csrfToken = (string) $request->getPayload()->get('_csrf');
        $status = false;
        if (!$this->isCsrfTokenValid('guestcsrf', $csrfToken)) {
            $errorMessage = $this->translator->trans('dockontrol.global.invalid_csrf_token');
        } else {
            try {
                $this->userActionLogHelper->addUserActionLog('Removed guest pass ' . $guest->getHash()->toString(), $user);
                $this->guestHelper->deleteGuestPass($guest);
                $status = true;
                $errorMessage = $this->translator->trans('dockontrol.guest_pass.messages.deleted_guest_pass');
                $this->addFlash('danger', $errorMessage);
            } catch (Throwable $throwable) {
                $errorMessage = $this->translator->trans('dockontrol.guest_pass.messages.failed_delete_guest') . $throwable->getMessage();
            }
        }
        return $this->json(['status' => $status, 'errorMessage' => $errorMessage]);
    }
}

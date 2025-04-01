<?php

declare(strict_types=1);

namespace App\Controller\PZ;

use App\Entity\SignupCode;
use App\Entity\User;
use App\Form\SignupType;
use App\Helper\SignupCodeHelper;
use RuntimeException;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;

#[Route('/signup')]
#[IsGranted('PUBLIC_ACCESS')]
class SignupController extends AbstractPZController
{
    public function __construct(private readonly SignupCodeHelper $signupCodeHelper)
    {
    }

    #[Route('/{hash}', name: 'dockontrol_signup_with_hash')]
    public function signup(
        Request $request,
        #[MapEntity(id: 'hash')] SignupCode $signupCode,
    ): Response {
        if ($this->getUser() instanceof User) {
            return $this->redirectToRoute('dockontrol_main');
        }

        if ($signupCode->isUsable()) {
            throw new RuntimeException('The signup code has expired.');
        }

        $user = new User();

        $signupForm = $this->createForm(SignupType::class, $user, [
            'apartment' => $signupCode->getApartment(),
        ]);
        $signupForm->handleRequest($request);

        if ($signupForm->isSubmitted() && $signupForm->isValid()) {
            try {
                $this->signupCodeHelper->createUserUsingSignupCode($signupCode, $user, $signupForm->get('password')->getData(), $signupForm->get('phone')->getData());
                $this->addFlash('success', 'Signup successful.');
                return $this->redirectToRoute('dockontrol_login');
            } catch (Throwable $exception) {
                $this->addFlash('danger', $exception->getMessage());
            }
        }

        return $this->render('pz/security/signup.html.twig', [
            'signupCode' => $signupCode,
            'signupForm' => $signupForm->createView(),
        ]);
    }
}

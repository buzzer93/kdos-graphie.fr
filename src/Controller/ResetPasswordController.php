<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Form\ResetPasswordRequestType;
use App\Form\ResetPasswordType;
use App\Repository\UserRepository;
use App\Service\ResetPasswordMailer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use SymfonyCasts\Bundle\ResetPassword\Controller\ResetPasswordControllerTrait;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

#[Route('/reset-password', name: 'app_reset_password_')]
final class ResetPasswordController extends AbstractController
{
    use ResetPasswordControllerTrait;

    public function __construct(
        private readonly ResetPasswordHelperInterface $resetPasswordHelper,
        private readonly ResetPasswordMailer $resetPasswordMailer,
    ) {
    }

    #[Route('', name: 'request', methods: ['GET', 'POST'])]
    public function request(Request $request, UserRepository $userRepository): Response
    {
        $form = $this->createForm(ResetPasswordRequestType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            return $this->processSendingPasswordResetEmail(
                (string) $form->get('email')->getData(),
                $userRepository,
            );
        }

        return $this->render('reset_password/request.html.twig', ['form' => $form]);
    }

    #[Route('/check-email', name: 'check_email', methods: ['GET'])]
    public function checkEmail(): Response
    {
        if (null === ($resetToken = $this->getTokenObjectFromSession())) {
            return $this->redirectToRoute('app_reset_password_request');
        }

        return $this->render('reset_password/check_email.html.twig', ['resetToken' => $resetToken]);
    }

    #[Route('/reset/{token}', name: 'reset', methods: ['GET', 'POST'])]
    public function reset(Request $request, UserPasswordHasherInterface $passwordHasher, ?string $token = null): Response
    {
        if ($token) {
            $this->storeTokenInSession($token);

            return $this->redirectToRoute('app_reset_password_reset');
        }

        $token = $this->getTokenFromSession();

        if (null === $token) {
            throw $this->createNotFoundException('Aucun token de reinitialisation trouve en session.');
        }

        try {
            /** @var User $user */
            $user = $this->resetPasswordHelper->validateTokenAndFetchUser($token);
        } catch (ResetPasswordExceptionInterface $e) {
            $this->addFlash('danger', 'Le lien de reinitialisation est invalide ou a expire. Veuillez faire une nouvelle demande.');

            return $this->redirectToRoute('app_reset_password_request');
        }

        $form = $this->createForm(ResetPasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->resetPasswordHelper->removeResetRequest($token);

            $hashedPassword = $passwordHasher->hashPassword($user, (string) $form->get('plainPassword')->getData());
            $user->setPassword($hashedPassword);

            $this->addFlash('success', 'Mot de passe mis a jour. Vous pouvez vous connecter.');

            return $this->redirectToRoute('app_login');
        }

        return $this->render('reset_password/reset.html.twig', ['form' => $form]);
    }

    private function processSendingPasswordResetEmail(string $emailFormData, UserRepository $userRepository): Response
    {
        $user = $userRepository->findOneBy(['email' => $emailFormData]);

        if (!$user instanceof User) {
            return $this->redirectToRoute('app_reset_password_check_email');
        }

        try {
            $resetToken = $this->resetPasswordHelper->generateResetToken($user);
        } catch (ResetPasswordExceptionInterface) {
            return $this->redirectToRoute('app_reset_password_check_email');
        }

        $resetUrl = $this->generateUrl(
            'app_reset_password_reset',
            ['token' => $resetToken->getToken()],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );

        $this->resetPasswordMailer->sendPasswordResetEmail($user, $resetUrl);
        $this->setTokenObjectInSession($resetToken);

        return $this->redirectToRoute('app_reset_password_check_email');
    }
}

<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;

final class ResetPasswordMailer
{
    public function __construct(
        private readonly MailerInterface $mailer,
        #[Autowire('%env(string:MAIL_FROM)%')]
        private readonly string $fromAddress,
    ) {
    }

    public function sendPasswordResetEmail(User $user, string $resetUrl): void
    {
        $this->mailer->send(
            (new TemplatedEmail())
                ->from($this->fromAddress)
                ->to((string) $user->getEmail())
                ->subject('Reinitialisation de votre mot de passe')
                ->htmlTemplate('emails/reset_password.html.twig')
                ->context(['resetUrl' => $resetUrl, 'user' => $user])
        );
    }
}

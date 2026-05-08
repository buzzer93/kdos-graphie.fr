<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;

final class ContactMailer
{
    public function __construct(
        private readonly MailerInterface $mailer,
        #[Autowire('%env(string:MAIL_FROM)%')]
        private readonly string $fromAddress,
        #[Autowire('%env(string:MAIL_ADMIN)%')]
        private readonly string $recipientAddress,
    ) {
    }

    /** @param array{name: string, email: string, subject: string, message: string} $data */
    public function sendContactMessage(array $data): void
    {
        $this->mailer->send(
            (new TemplatedEmail())
                ->from($this->fromAddress)
                ->to($this->recipientAddress)
                ->replyTo($data['email'])
                ->subject('[Contact] ' . $data['subject'])
                ->htmlTemplate('emails/contact_message.html.twig')
                ->context(['data' => $data])
        );
    }
}

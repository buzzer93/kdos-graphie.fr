<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Order;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;

final class OrderMailer
{
    public function __construct(
        private readonly MailerInterface $mailer,
        #[Autowire('%env(string:MAILER_FROM_ADDRESS)%')]
        private readonly string $fromAddress,
    ) {
    }

    public function sendOrderReceived(Order $order): void
    {
        $this->send(
            (new TemplatedEmail())
                ->from($this->fromAddress)
                ->to($order->getCustomerEmail())
                ->subject('Commande reçue: ' . $order->getReference())
                ->htmlTemplate('emails/order_received.html.twig')
                ->context(['order' => $order])
        );
    }

    public function sendOrderRefused(Order $order): void
    {
        $this->send(
            (new TemplatedEmail())
                ->from($this->fromAddress)
                ->to($order->getCustomerEmail())
                ->subject('Commande refusée: ' . $order->getReference())
                ->htmlTemplate('emails/order_refused.html.twig')
                ->context(['order' => $order])
        );
    }

    public function sendOrderConfirmedForPayment(Order $order): void
    {
        $this->send(
            (new TemplatedEmail())
                ->from($this->fromAddress)
                ->to($order->getCustomerEmail())
                ->subject('Commande confirmée, paiement à venir: ' . $order->getReference())
                ->htmlTemplate('emails/order_confirmed_for_payment.html.twig')
                ->context(['order' => $order])
        );
    }

    public function sendOrderDoneShipping(Order $order): void
    {
        $this->send(
            (new TemplatedEmail())
                ->from($this->fromAddress)
                ->to($order->getCustomerEmail())
                ->subject('Commande terminée: ' . $order->getReference())
                ->htmlTemplate('emails/order_done_shipping.html.twig')
                ->context(['order' => $order])
        );
    }

    private function send(TemplatedEmail $email): void
    {
        $this->mailer->send($email);
    }
}

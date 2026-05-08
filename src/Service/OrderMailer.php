<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Order;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;

final class OrderMailer
{
    public function __construct(
        private readonly MailerInterface $mailer,
        #[Autowire('%env(string:MAIL_FROM)%')]
        private readonly string $fromAddress,
        #[Autowire('%env(string:MAIL_ADMIN)%')]
        private readonly string $adminAddress,
    ) {
    }

    public function sendOrderReceived(Order $order): void
    {
        $this->send(
            (new TemplatedEmail())
                ->from($this->fromAddress)
                ->to($order->getCustomerEmail())
                ->subject('Demande reçue : ' . $order->getReference())
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
                ->subject('Commande refusée : ' . $order->getReference())
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
                ->subject('Commande confirmée : ' . $order->getReference())
                ->htmlTemplate('emails/order_confirmed_for_payment.html.twig')
                ->context(['order' => $order])
        );
    }

    public function sendPaymentReminder(Order $order): void
    {
        $this->send(
            (new TemplatedEmail())
                ->from($this->fromAddress)
                ->to($order->getCustomerEmail())
                ->subject('Rappel de paiement : ' . $order->getReference())
                ->htmlTemplate('emails/order_payment_reminder.html.twig')
                ->context(['order' => $order])
        );
    }

    public function sendOrderCancelled(Order $order): void
    {
        $this->send(
            (new TemplatedEmail())
                ->from($this->fromAddress)
                ->to($order->getCustomerEmail())
                ->subject('Commande annulée : ' . $order->getReference())
                ->htmlTemplate('emails/order_cancelled.html.twig')
                ->context(['order' => $order])
        );
    }

    public function sendOrderDoneShipping(Order $order): void
    {
        $this->send(
            (new TemplatedEmail())
                ->from($this->fromAddress)
                ->to($order->getCustomerEmail())
                ->subject('Commande expédiée : ' . $order->getReference())
                ->htmlTemplate('emails/order_done_shipping.html.twig')
                ->context(['order' => $order])
        );
    }

    public function sendAdminOrderPaidNotification(Order $order): void
    {
        $this->send(
            (new TemplatedEmail())
                ->from($this->fromAddress)
                ->to($this->adminAddress)
                ->subject('[Admin] Paiement reçu : ' . $order->getReference())
                ->htmlTemplate('emails/admin_order_paid.html.twig')
                ->context(['order' => $order])
        );
    }

    private function send(TemplatedEmail $email): void
    {
        $this->mailer->send($email);
    }
}

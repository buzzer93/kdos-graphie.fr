<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Order;
use App\Repository\OrderRepository;
use App\Service\StripePaymentService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/commande/paiement', name: 'app_payment_')]
final class PaymentController extends AbstractController
{
    #[Route('/{reference}', name: 'form', methods: ['GET'])]
    public function form(
        string $reference,
        OrderRepository $orderRepository,
        StripePaymentService $stripePaymentService,
        #[Autowire('%env(string:STRIPE_PUBLIC_KEY)%')]
        string $stripePublicKey,
    ): Response {
        $order = $orderRepository->findOneBy(['reference' => $reference]);

        if ($order === null) {
            throw $this->createNotFoundException('Commande introuvable.');
        }

        if ($order->getStatus() !== Order::STATUS_EN_ATTENTE_PAIEMENT || $order->getStripePaymentIntentId() === null) {
            return $this->render('payment/not_available.html.twig', ['order' => $order]);
        }

        $paymentIntent = $stripePaymentService->retrievePaymentIntent($order->getStripePaymentIntentId());

        return $this->render('payment/form.html.twig', [
            'order' => $order,
            'stripePublicKey' => $stripePublicKey,
            'clientSecret' => $paymentIntent->client_secret,
        ]);
    }

    #[Route('/succes/{reference}', name: 'success', methods: ['GET'])]
    public function success(string $reference, OrderRepository $orderRepository): Response
    {
        $order = $orderRepository->findOneBy(['reference' => $reference]);

        if ($order === null) {
            throw $this->createNotFoundException('Commande introuvable.');
        }

        $isConfirmed = in_array($order->getStatus(), [Order::STATUS_A_FAIRE, Order::STATUS_TERMINE], true);

        return $this->render('payment/success.html.twig', [
            'order' => $order,
            'isConfirmed' => $isConfirmed,
        ]);
    }
}

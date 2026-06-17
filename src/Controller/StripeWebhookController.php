<?php

declare(strict_types=1);

namespace App\Controller;

use App\Handler\StripeWebhookHandler;
use App\Service\StripePaymentService;
use Psr\Log\LoggerInterface;
use Stripe\Exception\SignatureVerificationException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class StripeWebhookController extends AbstractController
{
    #[Route('/stripe/webhook', name: 'app_stripe_webhook', methods: ['POST'])]
    public function webhook(
        Request $request,
        StripePaymentService $stripePaymentService,
        StripeWebhookHandler $stripeWebhookHandler,
        LoggerInterface $logger,
        #[Autowire('%env(string:STRIPE_WEBHOOK_SECRET)%')]
        string $webhookSecret,
    ): Response {
        $payload = (string) $request->getContent();
        $sigHeader = (string) $request->headers->get('stripe-signature', '');

        try {
            $event = $stripePaymentService->constructWebhookEvent($payload, $sigHeader, $webhookSecret);
        } catch (SignatureVerificationException $e) {
            $logger->warning('Stripe webhook: invalid signature', ['error' => $e->getMessage()]);

            return new Response('Invalid signature', Response::HTTP_BAD_REQUEST);
        } catch (\UnexpectedValueException $e) {
            $logger->warning('Stripe webhook: invalid payload', ['error' => $e->getMessage()]);

            return new Response('Invalid payload', Response::HTTP_BAD_REQUEST);
        }

        $stripeWebhookHandler->handle($event);

        return new Response('OK', Response::HTTP_OK);
    }
}

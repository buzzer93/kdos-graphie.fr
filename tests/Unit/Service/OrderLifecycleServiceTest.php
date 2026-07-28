<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Order;
use App\Message\AdminOrderPaidNotification;
use App\Service\OrderLifecycleService;
use App\Service\OrderMailer;
use App\Service\StripePaymentService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Stripe\PaymentIntent;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class OrderLifecycleServiceTest extends TestCase
{
    // --- accept() ---

    public function testAcceptTransitionsToAwaitingPaymentAndSendsEmail(): void
    {
        $order = $this->createOrder(Order::STATUS_A_CONFIRMER);
        [$em, $mailer, $symfonyMailer, $bus] = $this->createDependencies();

        $em->expects(self::once())->method('flush');
        $symfonyMailer->expects(self::once())->method('send');

        $result = (new OrderLifecycleService($em, $mailer, $bus, $stripe, $urlGenerator))->accept($order);

        self::assertSame(Order::STATUS_EN_ATTENTE_PAIEMENT, $order->getStatus());
        self::assertSame('success', $result->level);
    }

    public function testAcceptBlocksWhenStatusIsNotAConfirmer(): void
    {
        foreach ([
            Order::STATUS_EN_ATTENTE_PAIEMENT,
            Order::STATUS_A_FAIRE,
            Order::STATUS_TERMINE,
            Order::STATUS_REFUSE,
            Order::STATUS_ANNULE,
        ] as $status) {
            $order = $this->createOrder($status);
            [$em, $mailer, $symfonyMailer, $bus] = $this->createDependencies();

            $em->expects(self::never())->method('flush');
            $symfonyMailer->expects(self::never())->method('send');

            $result = (new OrderLifecycleService($em, $mailer, $bus, $stripe, $urlGenerator))->accept($order);

            self::assertSame('warning', $result->level, "accept() should return warning for status $status");
            self::assertSame($status, $order->getStatus());
        }
    }

    // --- remindPayment() ---

    public function testRemindPaymentSendsEmailWhenEnAttentePaiement(): void
    {
        $order = $this->createOrder(Order::STATUS_EN_ATTENTE_PAIEMENT);
        [$em, $mailer, $symfonyMailer, $bus] = $this->createDependencies();

        $em->expects(self::never())->method('flush');
        $symfonyMailer->expects(self::once())->method('send');

        $result = (new OrderLifecycleService($em, $mailer, $bus, $stripe, $urlGenerator))->remindPayment($order);

        self::assertSame('success', $result->level);
    }

    public function testRemindPaymentBlocksWhenStatusIsNotEnAttentePaiement(): void
    {
        foreach ([
            Order::STATUS_A_CONFIRMER,
            Order::STATUS_A_FAIRE,
            Order::STATUS_TERMINE,
            Order::STATUS_REFUSE,
            Order::STATUS_ANNULE,
        ] as $status) {
            $order = $this->createOrder($status);
            [$em, $mailer, $symfonyMailer, $bus] = $this->createDependencies();

            $em->expects(self::never())->method('flush');
            $symfonyMailer->expects(self::never())->method('send');

            $result = (new OrderLifecycleService($em, $mailer, $bus, $stripe, $urlGenerator))->remindPayment($order);

            self::assertSame('warning', $result->level, "remindPayment() should return warning for status $status");
        }
    }

    // --- reject() ---

    public function testRejectSetsRefusedStatusAndSendsEmail(): void
    {
        $order = $this->createOrder(Order::STATUS_A_CONFIRMER);
        [$em, $mailer, $symfonyMailer, $bus] = $this->createDependencies();

        $em->expects(self::once())->method('flush');
        $symfonyMailer->expects(self::once())->method('send');

        $result = (new OrderLifecycleService($em, $mailer, $bus, $stripe, $urlGenerator))->reject($order, 'Fichier illisible');

        self::assertSame(Order::STATUS_REFUSE, $order->getStatus());
        self::assertSame('Fichier illisible', $order->getDecisionReason());
        self::assertSame('success', $result->level);
    }

    public function testRejectIsAllowedFromEnAttentePaiement(): void
    {
        $order = $this->createOrder(Order::STATUS_EN_ATTENTE_PAIEMENT);
        [$em, $mailer, $symfonyMailer, $bus] = $this->createDependencies();

        $em->expects(self::once())->method('flush');
        $symfonyMailer->expects(self::once())->method('send');

        $result = (new OrderLifecycleService($em, $mailer, $bus, $stripe, $urlGenerator))->reject($order, 'Paiement expiré');

        self::assertSame(Order::STATUS_REFUSE, $order->getStatus());
        self::assertSame('success', $result->level);
    }

    public function testRejectBlocksWhenReasonIsBlank(): void
    {
        foreach (['', '   ', "\t"] as $blank) {
            $order = $this->createOrder(Order::STATUS_A_CONFIRMER);
            [$em, $mailer, $symfonyMailer, $bus] = $this->createDependencies();

            $em->expects(self::never())->method('flush');
            $symfonyMailer->expects(self::never())->method('send');

            $result = (new OrderLifecycleService($em, $mailer, $bus, $stripe, $urlGenerator))->reject($order, $blank);

            self::assertSame('danger', $result->level);
            self::assertSame(Order::STATUS_A_CONFIRMER, $order->getStatus());
        }
    }

    public function testRejectBlocksWhenStatusIsAFaire(): void
    {
        $order = $this->createOrder(Order::STATUS_A_FAIRE);
        [$em, $mailer, $symfonyMailer, $bus] = $this->createDependencies();

        $em->expects(self::never())->method('flush');
        $symfonyMailer->expects(self::never())->method('send');

        $result = (new OrderLifecycleService($em, $mailer, $bus, $stripe, $urlGenerator))->reject($order, 'Motif valide');

        self::assertSame('warning', $result->level);
        self::assertSame(Order::STATUS_A_FAIRE, $order->getStatus());
    }

    public function testRejectBlocksWhenStatusIsTerminal(): void
    {
        foreach ([Order::STATUS_TERMINE, Order::STATUS_REFUSE, Order::STATUS_ANNULE] as $status) {
            $order = $this->createOrder($status);
            [$em, $mailer, $symfonyMailer, $bus] = $this->createDependencies();

            $em->expects(self::never())->method('flush');
            $symfonyMailer->expects(self::never())->method('send');

            $result = (new OrderLifecycleService($em, $mailer, $bus, $stripe, $urlGenerator))->reject($order, 'Motif valide');

            self::assertSame('warning', $result->level, "reject() should return warning for status $status");
        }
    }

    // --- cancel() ---

    public function testCancelSetsAnnuleStatusAndSendsEmail(): void
    {
        $order = $this->createOrder(Order::STATUS_A_CONFIRMER);
        [$em, $mailer, $symfonyMailer, $bus] = $this->createDependencies();

        $em->expects(self::once())->method('flush');
        $symfonyMailer->expects(self::once())->method('send');

        $result = (new OrderLifecycleService($em, $mailer, $bus, $stripe, $urlGenerator))->cancel($order, 'Commande dupliquée');

        self::assertSame(Order::STATUS_ANNULE, $order->getStatus());
        self::assertSame('Commande dupliquée', $order->getDecisionReason());
        self::assertSame('success', $result->level);
    }

    public function testCancelIsAllowedFromEnAttentePaiement(): void
    {
        $order = $this->createOrder(Order::STATUS_EN_ATTENTE_PAIEMENT);
        [$em, $mailer, $symfonyMailer, $bus] = $this->createDependencies();

        $em->expects(self::once())->method('flush');
        $symfonyMailer->expects(self::once())->method('send');

        $result = (new OrderLifecycleService($em, $mailer, $bus, $stripe, $urlGenerator))->cancel($order, 'Client désiste');

        self::assertSame(Order::STATUS_ANNULE, $order->getStatus());
        self::assertSame('success', $result->level);
    }

    public function testCancelIsAllowedFromAFaire(): void
    {
        $order = $this->createOrder(Order::STATUS_A_FAIRE);
        [$em, $mailer, $symfonyMailer, $bus] = $this->createDependencies();

        $em->expects(self::once())->method('flush');
        $symfonyMailer->expects(self::once())->method('send');

        $result = (new OrderLifecycleService($em, $mailer, $bus, $stripe, $urlGenerator))->cancel($order, 'Problème technique');

        self::assertSame(Order::STATUS_ANNULE, $order->getStatus());
        self::assertSame('success', $result->level);
    }

    public function testCancelBlocksWhenReasonIsBlank(): void
    {
        foreach (['', '   ', "\t"] as $blank) {
            $order = $this->createOrder(Order::STATUS_A_CONFIRMER);
            [$em, $mailer, $symfonyMailer, $bus] = $this->createDependencies();

            $em->expects(self::never())->method('flush');
            $symfonyMailer->expects(self::never())->method('send');

            $result = (new OrderLifecycleService($em, $mailer, $bus, $stripe, $urlGenerator))->cancel($order, $blank);

            self::assertSame('danger', $result->level);
            self::assertSame(Order::STATUS_A_CONFIRMER, $order->getStatus());
        }
    }

    public function testCancelBlocksWhenStatusIsTerminal(): void
    {
        foreach ([Order::STATUS_TERMINE, Order::STATUS_REFUSE, Order::STATUS_ANNULE] as $status) {
            $order = $this->createOrder($status);
            [$em, $mailer, $symfonyMailer, $bus] = $this->createDependencies();

            $em->expects(self::never())->method('flush');
            $symfonyMailer->expects(self::never())->method('send');

            $result = (new OrderLifecycleService($em, $mailer, $bus, $stripe, $urlGenerator))->cancel($order, 'Motif valide');

            self::assertSame('warning', $result->level, "cancel() should return warning for status $status");
        }
    }

    public function testCancelAppendsPrefixedReasonToExistingNotes(): void
    {
        $order = $this->createOrder(Order::STATUS_A_CONFIRMER);
        $order->setNotes('Note initiale');
        $em = $this->createStub(EntityManagerInterface::class);
        $bus = $this->createStub(MessageBusInterface::class);
        $stripe = $this->createStub(StripePaymentService::class);
        $urlGenerator = $this->createStub(UrlGeneratorInterface::class);
        $mailer = new OrderMailer($this->createStub(MailerInterface::class), 'no-reply@example.test', 'admin@example.test');

        (new OrderLifecycleService($em, $mailer, $bus, $stripe, $urlGenerator))->cancel($order, 'Problème client');

        self::assertStringContainsString('Note initiale', (string) $order->getNotes());
        self::assertStringContainsString('Annulee: Problème client', (string) $order->getNotes());
    }

    // --- markAsPaid() ---

    public function testMarkAsPaidTransitionsToAFaireAndDispatchesMessage(): void
    {
        $order = $this->createOrder(Order::STATUS_EN_ATTENTE_PAIEMENT);
        [$em, $mailer, $symfonyMailer, , $stripe, $urlGenerator] = $this->createDependencies();
        $bus = $this->createMock(MessageBusInterface::class);

        $em->expects(self::once())->method('flush');
        $symfonyMailer->expects(self::never())->method('send');
        $bus->expects(self::once())
            ->method('dispatch')
            ->with(self::isInstanceOf(AdminOrderPaidNotification::class))
            ->willReturn(new Envelope(new AdminOrderPaidNotification(0)));

        $result = (new OrderLifecycleService($em, $mailer, $bus, $stripe, $urlGenerator))->markAsPaid($order);

        self::assertSame(Order::STATUS_A_FAIRE, $order->getStatus());
        self::assertSame('success', $result->level);
    }

    public function testMarkAsPaidBlocksWhenStatusIsNotEnAttentePaiement(): void
    {
        foreach ([
            Order::STATUS_A_CONFIRMER,
            Order::STATUS_A_FAIRE,
            Order::STATUS_TERMINE,
            Order::STATUS_REFUSE,
            Order::STATUS_ANNULE,
        ] as $status) {
            $order = $this->createOrder($status);
            [$em, $mailer, $symfonyMailer, $bus] = $this->createDependencies();

            $em->expects(self::never())->method('flush');
            $symfonyMailer->expects(self::never())->method('send');

            $result = (new OrderLifecycleService($em, $mailer, $bus, $stripe, $urlGenerator))->markAsPaid($order);

            self::assertSame('warning', $result->level, "markAsPaid() should return warning for status $status");
            self::assertSame($status, $order->getStatus());
        }
    }

    // --- complete() ---

    public function testCompleteTransitionsToTermineAndSendsShippingEmail(): void
    {
        $order = $this->createOrder(Order::STATUS_A_FAIRE);
        [$em, $mailer, $symfonyMailer, $bus] = $this->createDependencies();

        $em->expects(self::once())->method('flush');
        $symfonyMailer->expects(self::once())->method('send');

        $result = (new OrderLifecycleService($em, $mailer, $bus, $stripe, $urlGenerator))->complete($order);

        self::assertSame(Order::STATUS_TERMINE, $order->getStatus());
        self::assertSame('success', $result->level);
    }

    public function testCompleteBlocksWhenStatusIsNotAFaire(): void
    {
        foreach ([
            Order::STATUS_A_CONFIRMER,
            Order::STATUS_EN_ATTENTE_PAIEMENT,
            Order::STATUS_TERMINE,
            Order::STATUS_REFUSE,
            Order::STATUS_ANNULE,
        ] as $status) {
            $order = $this->createOrder($status);
            [$em, $mailer, $symfonyMailer, $bus] = $this->createDependencies();

            $em->expects(self::never())->method('flush');
            $symfonyMailer->expects(self::never())->method('send');

            $result = (new OrderLifecycleService($em, $mailer, $bus, $stripe, $urlGenerator))->complete($order);

            self::assertSame('warning', $result->level, "complete() should return warning for status $status");
            self::assertSame($status, $order->getStatus());
        }
    }

    // --- helpers ---

    /**
     * @return array{EntityManagerInterface&MockObject, OrderMailer, MailerInterface&MockObject, MessageBusInterface, StripePaymentService, UrlGeneratorInterface}
     */
    private function createDependencies(): array
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $symfonyMailer = $this->createMock(MailerInterface::class);
        $mailer = new OrderMailer($symfonyMailer, 'no-reply@example.test', 'admin@example.test');
        $bus = $this->createStub(MessageBusInterface::class);

        $stripe = $this->createStub(StripePaymentService::class);
        $stripe->method('createPaymentIntent')->willReturn(PaymentIntent::constructFrom(['id' => 'pi_test_stub']));

        $urlGenerator = $this->createStub(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturn('https://example.test/pay/test');

        return [$em, $mailer, $symfonyMailer, $bus, $stripe, $urlGenerator];
    }

    private function createOrder(string $status): Order
    {
        return (new Order())
            ->setReference('ORD-TDD-LIFE-001')
            ->setStatus($status)
            ->setCustomerFirstName('Test')
            ->setCustomerLastName('Client')
            ->setCustomerEmail('client@example.test')
            ->setCustomerPhone('0600000000')
            ->setShippingAddress("1 rue du Test\n75000 Paris")
            ->setTotal(1000);
    }
}

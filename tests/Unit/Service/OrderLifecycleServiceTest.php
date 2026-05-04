<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Order;
use App\Service\OrderLifecycleService;
use App\Service\OrderMailer;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\MailerInterface;

final class OrderLifecycleServiceTest extends TestCase
{
    public function testAcceptTransitionsToAwaitingPaymentAndSendsEmail(): void
    {
        $order = $this->createOrder(Order::STATUS_A_CONFIRMER);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('flush');

        $symfonyMailer = $this->createMock(MailerInterface::class);
        $symfonyMailer->expects(self::once())->method('send');

        $mailer = new OrderMailer($symfonyMailer, 'no-reply@example.test');

        $service = new OrderLifecycleService($entityManager, $mailer);
        $result = $service->accept($order);

        self::assertSame(Order::STATUS_EN_ATTENTE_PAIEMENT, $order->getStatus());
        self::assertSame('success', $result->level);
    }

    public function testRejectRequiresReason(): void
    {
        $order = $this->createOrder(Order::STATUS_A_CONFIRMER);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::never())->method('flush');

        $symfonyMailer = $this->createMock(MailerInterface::class);
        $symfonyMailer->expects(self::never())->method('send');

        $mailer = new OrderMailer($symfonyMailer, 'no-reply@example.test');

        $service = new OrderLifecycleService($entityManager, $mailer);
        $result = $service->reject($order, '   ');

        self::assertSame('danger', $result->level);
        self::assertSame(Order::STATUS_A_CONFIRMER, $order->getStatus());
    }

    public function testRejectSetsRefusedStatusAndReason(): void
    {
        $order = $this->createOrder(Order::STATUS_A_CONFIRMER);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('flush');

        $symfonyMailer = $this->createMock(MailerInterface::class);
        $symfonyMailer->expects(self::once())->method('send');

        $mailer = new OrderMailer($symfonyMailer, 'no-reply@example.test');

        $service = new OrderLifecycleService($entityManager, $mailer);
        $result = $service->reject($order, 'Fichier illisible');

        self::assertSame(Order::STATUS_REFUSE, $order->getStatus());
        self::assertSame('Fichier illisible', $order->getDecisionReason());
        self::assertSame('success', $result->level);
    }

    public function testMarkAsPaidTransitionsToATraiter(): void
    {
        $order = $this->createOrder(Order::STATUS_EN_ATTENTE_PAIEMENT);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('flush');

        $symfonyMailer = $this->createMock(MailerInterface::class);
        $symfonyMailer->expects(self::never())->method('send');

        $mailer = new OrderMailer($symfonyMailer, 'no-reply@example.test');

        $service = new OrderLifecycleService($entityManager, $mailer);
        $result = $service->markAsPaid($order);

        self::assertSame(Order::STATUS_A_FAIRE, $order->getStatus());
        self::assertSame('success', $result->level);
    }

    public function testCompleteTransitionsToTermineAndSendsShippingEmail(): void
    {
        $order = $this->createOrder(Order::STATUS_A_FAIRE);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('flush');

        $symfonyMailer = $this->createMock(MailerInterface::class);
        $symfonyMailer->expects(self::once())->method('send');

        $mailer = new OrderMailer($symfonyMailer, 'no-reply@example.test');

        $service = new OrderLifecycleService($entityManager, $mailer);
        $result = $service->complete($order);

        self::assertSame(Order::STATUS_TERMINE, $order->getStatus());
        self::assertSame('success', $result->level);
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

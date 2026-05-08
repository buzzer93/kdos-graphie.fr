<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Order;
use App\Service\OrderAdminActionAvailabilityResolver;
use PHPUnit\Framework\TestCase;

final class OrderAdminActionAvailabilityResolverTest extends TestCase
{
    private OrderAdminActionAvailabilityResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new OrderAdminActionAvailabilityResolver();
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideAConfirmerActions')]
    public function testAConfirmer(string $method, bool $expected): void
    {
        $actions = $this->resolver->resolve($this->order(Order::STATUS_A_CONFIRMER));
        self::assertSame($expected, $actions->$method);
    }

    /** @return iterable<string, array{string, bool}> */
    public static function provideAConfirmerActions(): iterable
    {
        yield 'canAccept'        => ['canAccept', true];
        yield 'canReject'        => ['canReject', true];
        yield 'canCancel'        => ['canCancel', true];
        yield 'canComplete'      => ['canComplete', false];
        yield 'canMarkPaid'      => ['canMarkPaid', false];
        yield 'canRemindPayment' => ['canRemindPayment', false];
        yield 'canRequestInfo'   => ['canRequestInfo', true];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideEnAttentePaiementActions')]
    public function testEnAttentePaiement(string $method, bool $expected): void
    {
        $actions = $this->resolver->resolve($this->order(Order::STATUS_EN_ATTENTE_PAIEMENT));
        self::assertSame($expected, $actions->$method);
    }

    /** @return iterable<string, array{string, bool}> */
    public static function provideEnAttentePaiementActions(): iterable
    {
        yield 'canAccept'        => ['canAccept', false];
        yield 'canReject'        => ['canReject', true];
        yield 'canCancel'        => ['canCancel', true];
        yield 'canComplete'      => ['canComplete', false];
        yield 'canMarkPaid'      => ['canMarkPaid', true];
        yield 'canRemindPayment' => ['canRemindPayment', true];
        yield 'canRequestInfo'   => ['canRequestInfo', true];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideAFaireActions')]
    public function testAFaire(string $method, bool $expected): void
    {
        $actions = $this->resolver->resolve($this->order(Order::STATUS_A_FAIRE));
        self::assertSame($expected, $actions->$method);
    }

    /** @return iterable<string, array{string, bool}> */
    public static function provideAFaireActions(): iterable
    {
        yield 'canAccept'        => ['canAccept', false];
        yield 'canReject'        => ['canReject', false];
        yield 'canCancel'        => ['canCancel', true];
        yield 'canComplete'      => ['canComplete', true];
        yield 'canMarkPaid'      => ['canMarkPaid', false];
        yield 'canRemindPayment' => ['canRemindPayment', false];
        yield 'canRequestInfo'   => ['canRequestInfo', true];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideTerminalStatuses')]
    public function testTerminalStatusesAllowNothing(string $status): void
    {
        $actions = $this->resolver->resolve($this->order($status));

        self::assertFalse($actions->canAccept);
        self::assertFalse($actions->canReject);
        self::assertFalse($actions->canCancel);
        self::assertFalse($actions->canComplete);
        self::assertFalse($actions->canMarkPaid);
        self::assertFalse($actions->canRemindPayment);
        self::assertFalse($actions->canRequestInfo);
    }

    /** @return iterable<string, array{string}> */
    public static function provideTerminalStatuses(): iterable
    {
        yield 'termine' => [Order::STATUS_TERMINE];
        yield 'refuse'  => [Order::STATUS_REFUSE];
        yield 'annule'  => [Order::STATUS_ANNULE];
    }

    private function order(string $status): Order
    {
        return (new Order())->setReference('ORD-TEST')->setStatus($status)->setTotal(0);
    }
}

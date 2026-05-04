<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Admin;

use App\Entity\Order;
use App\Repository\OrderRepository;
use App\Tests\Functional\AbstractWebTestCase;

final class OrderControllerTest extends AbstractWebTestCase
{
    public function testOrderIndexRequiresAuthentication(): void
    {
        $client = $this->createClientWithFreshDatabase();
        $client->request('GET', '/admin/orders/');

        self::assertResponseRedirects('/login');
    }

    public function testOrderCrudFlow(): void
    {
        $client = $this->createAuthenticatedClient();

        $client->request('GET', '/admin/orders/new');
        $client->submitForm('Enregistrer', [
            'order[reference]' => 'ORD-TDD-001',
            'order[status]' => Order::STATUS_PENDING,
            'order[customerName]' => 'John Doe',
            'order[customerEmail]' => 'john@example.test',
            'order[notes]' => 'Commande de test',
            'order[items][0][productName]' => 'Produit A',
            'order[items][0][unitPrice]' => '1200',
            'order[items][0][quantity]' => '2',
        ]);

        self::assertResponseRedirects('/admin/orders/');

        $orderRepository = static::getContainer()->get(OrderRepository::class);
        $order = $orderRepository->findOneBy(['reference' => 'ORD-TDD-001']);
        self::assertInstanceOf(Order::class, $order);
        self::assertSame(2400, $order->getTotal());
        self::assertCount(1, $order->getItems());

        $client->request('GET', '/admin/orders/' . $order->getId() . '/edit');
        $client->submitForm('Enregistrer', [
            'order[reference]' => 'ORD-TDD-001',
            'order[status]' => Order::STATUS_CONFIRMED,
            'order[customerName]' => 'John Doe MAJ',
            'order[customerEmail]' => 'john.maj@example.test',
            'order[notes]' => 'Commande mise a jour',
            'order[items][0][productName]' => 'Produit A',
            'order[items][0][unitPrice]' => '1500',
            'order[items][0][quantity]' => '3',
        ]);

        self::assertResponseRedirects('/admin/orders/');

        $updatedOrder = $orderRepository->find($order->getId());
        self::assertSame(Order::STATUS_CONFIRMED, $updatedOrder?->getStatus());
        self::assertSame(4500, $updatedOrder?->getTotal());
        self::assertSame('John Doe MAJ', $updatedOrder?->getCustomerName());

        $client->request('POST', '/admin/orders/' . $order->getId() . '/delete', [
            '_token' => $this->csrfToken('delete_order_' . $order->getId()),
        ]);

        self::assertResponseRedirects('/admin/orders/');
        self::assertNull($orderRepository->find($order->getId()));
    }
}

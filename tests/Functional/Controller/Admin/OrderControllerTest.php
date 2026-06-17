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

        $crawler = $client->request('GET', '/admin/orders/new');
        $token = (string) $crawler->filter('input[name="order[_token]"]')->attr('value');

        $client->request('POST', '/admin/orders/new', [
            'order' => [
                '_token' => $token,
                'reference' => 'ORD-TDD-001',
                'customerFirstName' => 'John',
                'customerLastName' => 'Doe',
                'customerEmail' => 'john@example.test',
                'customerPhone' => '0600000001',
                'shippingAddress' => "10 rue de Paris\n75010 Paris",
                'additionalInfo' => 'Batiment A',
                'notes' => 'Commande de test',
                'items' => [[
                    'productName' => 'Produit A',
                    'unitPrice' => '1200',
                    'quantity' => '2',
                ]],
            ],
        ]);

        self::assertResponseRedirects('/admin/orders/');

        $orderRepository = static::getContainer()->get(OrderRepository::class);
        $order = $orderRepository->findOneBy(['reference' => 'ORD-TDD-001']);
        self::assertInstanceOf(Order::class, $order);
        self::assertSame(2400, $order->getTotal());
        self::assertCount(1, $order->getItems());

        $crawler = $client->request('GET', '/admin/orders/' . $order->getId() . '/edit');
        $token = (string) $crawler->filter('input[name="order[_token]"]')->attr('value');

        $client->request('POST', '/admin/orders/' . $order->getId() . '/edit', [
            'order' => [
                '_token' => $token,
                'reference' => 'ORD-TDD-001',
                'customerFirstName' => 'John',
                'customerLastName' => 'Doe MAJ',
                'customerEmail' => 'john.maj@example.test',
                'customerPhone' => '0600000002',
                'shippingAddress' => "11 rue de Paris\n75010 Paris",
                'additionalInfo' => 'Batiment B',
                'notes' => 'Commande mise a jour',
                'items' => [[
                    'productName' => 'Produit A',
                    'unitPrice' => '1500',
                    'quantity' => '3',
                ]],
            ],
        ]);

        self::assertResponseRedirects('/admin/orders/');

        $orderRepository = static::getContainer()->get(OrderRepository::class);
        $updatedOrder = $orderRepository->find($order->getId());
        self::assertSame(Order::STATUS_A_CONFIRMER, $updatedOrder?->getStatus());
        self::assertSame(4500, $updatedOrder?->getTotal());
        self::assertSame('John Doe MAJ', $updatedOrder?->getCustomerName());

        $crawler = $client->request('GET', '/admin/orders/' . $order->getId() . '/edit');
        $client->submit($crawler->filter('form[action="/admin/orders/' . $order->getId() . '/accept"]')->form());

        $crawler = $client->request('GET', '/admin/orders/' . $order->getId() . '/edit');
        $client->submit($crawler->filter('form[action="/admin/orders/' . $order->getId() . '/mark-paid"]')->form());

        $crawler = $client->request('GET', '/admin/orders/' . $order->getId() . '/edit');
        $client->submit($crawler->filter('form[action="/admin/orders/' . $order->getId() . '/complete"]')->form());

        $this->getEntityManager()->clear();
        $orderRepository = static::getContainer()->get(OrderRepository::class);
        self::assertSame(Order::STATUS_TERMINE, $orderRepository->find($order->getId())?->getStatus());

        $crawler = $client->request('GET', '/admin/orders/');
        $deleteForm = $crawler->filter('form[action="/admin/orders/' . $order->getId() . '/delete"]')->form();
        $client->submit($deleteForm);

        self::assertResponseRedirects('/admin/orders/');
        $orderRepository = static::getContainer()->get(OrderRepository::class);
        self::assertNull($orderRepository->find($order->getId()));
    }

    public function testOrderLifecycleActionsFlow(): void
    {
        $client = $this->createAuthenticatedClient();

        $crawler = $client->request('GET', '/admin/orders/new');
        $token = (string) $crawler->filter('input[name="order[_token]"]')->attr('value');

        $client->request('POST', '/admin/orders/new', [
            'order' => [
                '_token' => $token,
                'reference' => 'ORD-TDD-LIFE-001',
                'customerFirstName' => 'Alice',
                'customerLastName' => 'Doe',
                'customerEmail' => 'alice@example.test',
                'customerPhone' => '0600000003',
                'shippingAddress' => "1 avenue du Test\n31000 Toulouse",
                'additionalInfo' => '',
                'notes' => '',
                'items' => [[
                    'productName' => 'Produit B',
                    'unitPrice' => '1000',
                    'quantity' => '1',
                ]],
            ],
        ]);

        self::assertResponseRedirects('/admin/orders/');

        $orderRepository = static::getContainer()->get(OrderRepository::class);
        $order = $orderRepository->findOneBy(['reference' => 'ORD-TDD-LIFE-001']);
        self::assertInstanceOf(Order::class, $order);

        $crawler = $client->request('GET', '/admin/orders/' . $order->getId() . '/edit');
        $client->submit($crawler->filter('form[action="/admin/orders/' . $order->getId() . '/accept"]')->form());
        self::assertResponseRedirects('/admin/orders/' . $order->getId() . '/edit');
        $this->getEntityManager()->clear();
        $orderRepository = static::getContainer()->get(OrderRepository::class);
        self::assertSame(Order::STATUS_EN_ATTENTE_PAIEMENT, $orderRepository->find($order->getId())?->getStatus());

        $crawler = $client->request('GET', '/admin/orders/' . $order->getId() . '/edit');
        $client->submit($crawler->filter('form[action="/admin/orders/' . $order->getId() . '/mark-paid"]')->form());
        self::assertResponseRedirects('/admin/orders/' . $order->getId() . '/edit');
        $this->getEntityManager()->clear();
        $orderRepository = static::getContainer()->get(OrderRepository::class);
        self::assertSame(Order::STATUS_A_FAIRE, $orderRepository->find($order->getId())?->getStatus());

        $crawler = $client->request('GET', '/admin/orders/' . $order->getId() . '/edit');
        $client->submit($crawler->filter('form[action="/admin/orders/' . $order->getId() . '/complete"]')->form());
        self::assertResponseRedirects('/admin/orders/' . $order->getId() . '/edit');
        $this->getEntityManager()->clear();
        $orderRepository = static::getContainer()->get(OrderRepository::class);
        self::assertSame(Order::STATUS_TERMINE, $orderRepository->find($order->getId())?->getStatus());
    }
}

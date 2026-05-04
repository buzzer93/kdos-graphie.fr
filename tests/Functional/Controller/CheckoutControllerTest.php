<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\Order;
use App\Entity\Product;
use App\Repository\OrderRepository;
use App\Tests\Functional\AbstractWebTestCase;

final class CheckoutControllerTest extends AbstractWebTestCase
{
    public function testCheckoutRedirectsToCartWhenCartIsEmpty(): void
    {
        $client = $this->createClientWithFreshDatabase();

        $client->request('GET', '/commande/');

        self::assertResponseRedirects('/panier/');
    }

    public function testCheckoutCreatesOrderAndClearsCart(): void
    {
        $client = $this->createClientWithFreshDatabase();
        $entityManager = $this->getEntityManager();

        $product = (new Product())
            ->setName('Poster Premium')
            ->setSlug('poster-premium')
            ->setPrice(3200)
            ->setStock(20)
            ->setIsVisible(true);

        $entityManager->persist($product);
        $entityManager->flush();

        $client->request('GET', '/catalogue/' . $product->getSlug());

        $client->submitForm('Ajouter au panier', [
            'quantity' => 2,
            'customization_text' => 'Hello TDD',
        ]);

        self::assertResponseRedirects('/panier/');

        $client->request('GET', '/commande/');
        self::assertResponseIsSuccessful();

        $client->submitForm('Confirmer la demande', [
            'checkout_order[customerFirstName]' => 'Nina',
            'checkout_order[customerLastName]' => 'Martin',
            'checkout_order[customerEmail]' => 'nina@example.test',
            'checkout_order[customerPhone]' => '0600000100',
            'checkout_order[shippingAddress]' => "12 rue du Test\n75011 Paris",
            'checkout_order[additionalInfo]' => 'Digicode 1234',
        ]);

        self::assertResponseStatusCodeSame(302);
        $location = (string) $client->getResponse()->headers->get('Location');
        self::assertStringContainsString('/commande/confirmation/', $location);

        $orderRepository = static::getContainer()->get(OrderRepository::class);
        $order = $orderRepository->findOneBy(['customerEmail' => 'nina@example.test']);
        self::assertInstanceOf(Order::class, $order);
        self::assertSame(Order::STATUS_A_CONFIRMER, $order->getStatus());
        self::assertSame(6400, $order->getTotal());
        self::assertCount(1, $order->getItems());

        $client->request('GET', '/panier/');
        self::assertSelectorTextContains('body', 'Votre panier est vide.');
    }
}

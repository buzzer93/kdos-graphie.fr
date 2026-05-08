<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\Product;
use App\Tests\Functional\AbstractWebTestCase;

final class CartControllerTest extends AbstractWebTestCase
{
    public function testCartPageShowsEmptyStateWhenNoLine(): void
    {
        $client = $this->createClientWithFreshDatabase();

        $client->request('GET', '/panier/');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'Votre panier est vide.');
    }

    public function testAddUpdateAndRemoveLineFlow(): void
    {
        $client = $this->createClientWithFreshDatabase();
        $entityManager = $this->getEntityManager();

        $product = (new Product())
            ->setName('Affiche Personnalisee')
            ->setSlug('affiche-personnalisee')
            ->setPrice(2500)
            ->setIsVisible(true);

        $entityManager->persist($product);
        $entityManager->flush();

        $client->request('GET', '/catalogue/' . $product->getSlug());
        $client->submitForm('Ajouter au panier', [
            'quantity' => 2,
            'customization_text' => 'Texte perso',
        ]);

        self::assertResponseRedirects('/panier/');
        $client->followRedirect();
        self::assertSelectorTextContains('body', 'Affiche Personnalisee');
        self::assertSelectorTextContains('body', 'Articles: 2');

        $session = $client->getRequest()->getSession();
        $cart = $session->get('cart', ['lines' => []]);
        $lineId = $cart['lines'][0]['id'] ?? null;
        self::assertNotNull($lineId);

        $updateForm = $client->getCrawler()->filter('form[action$="/quantite"]')->first()->form([
            'quantity' => 4,
        ]);
        $client->submit($updateForm);

        self::assertResponseRedirects('/panier/');
        $client->followRedirect();
        self::assertSelectorTextContains('body', 'Articles: 4');

        $removeForm = $client->getCrawler()->filter('form[action$="/supprimer"]')->first()->form();
        $client->submit($removeForm);

        self::assertResponseRedirects('/panier/');
        $client->followRedirect();
        self::assertSelectorTextContains('body', 'Votre panier est vide.');
    }
}

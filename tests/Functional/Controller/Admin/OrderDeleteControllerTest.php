<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Admin;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Repository\OrderArchiveRepository;
use App\Repository\OrderRepository;
use App\Tests\Functional\AbstractWebTestCase;

final class OrderDeleteControllerTest extends AbstractWebTestCase
{
    public function testDeleteIsForbiddenForNonFinalStatus(): void
    {
        $client = $this->createAuthenticatedClient();
        $entityManager = $this->getEntityManager();
        $client->request('GET', '/admin/orders/');

        $order = $this->createOrder('ORD-DEL-LOCK-001', Order::STATUS_A_CONFIRMER);
        $entityManager->persist($order);
        $entityManager->flush();

        $crawler = $client->request('GET', '/admin/orders/');
        $deleteForm = $crawler->filter('form[action="/admin/orders/' . $order->getId() . '/delete"]')->form();
        $client->submit($deleteForm);

        self::assertResponseRedirects('/admin/orders/' . $order->getId() . '/edit');

        $orderRepository = static::getContainer()->get(OrderRepository::class);
        self::assertNotNull($orderRepository->find($order->getId()));

        $archiveRepository = static::getContainer()->get(OrderArchiveRepository::class);
        self::assertNull($archiveRepository->findOneBy(['reference' => 'ORD-DEL-LOCK-001']));
    }

    public function testDeleteArchivesAndRemovesFinalOrder(): void
    {
        $client = $this->createAuthenticatedClient();
        $entityManager = $this->getEntityManager();
        $client->request('GET', '/admin/orders/');

        $projectDir = (string) static::getContainer()->getParameter('kernel.project_dir');
        $storageDir = $projectDir . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'media' . DIRECTORY_SEPARATOR . 'customizations';
        if (!is_dir($storageDir)) {
            mkdir($storageDir, 0775, true);
        }

        file_put_contents($storageDir . DIRECTORY_SEPARATOR . 'archive-proof.pdf', 'proof');

        $order = $this->createOrder('ORD-DEL-OK-001', Order::STATUS_TERMINE);
        $item = (new OrderItem())
            ->setProductName('Produit archive')
            ->setUnitPrice(1000)
            ->setQuantity(1)
            ->setCustomizationFilePath('archive-proof.pdf');
        $order->addItem($item);

        $entityManager->persist($order);
        $entityManager->flush();

        $crawler = $client->request('GET', '/admin/orders/');
        $deleteForm = $crawler->filter('form[action="/admin/orders/' . $order->getId() . '/delete"]')->form();
        $client->submit($deleteForm);

        self::assertResponseRedirects('/admin/orders/');

        $orderRepository = static::getContainer()->get(OrderRepository::class);
        self::assertNull($orderRepository->find($order->getId()));

        $archiveRepository = static::getContainer()->get(OrderArchiveRepository::class);
        $archive = $archiveRepository->findOneBy(['reference' => 'ORD-DEL-OK-001']);
        self::assertNotNull($archive);
        self::assertSame(Order::STATUS_TERMINE, $archive->getStatus());
        self::assertSame('admin@test.local', $archive->getArchivedBy());
        self::assertFileDoesNotExist($storageDir . DIRECTORY_SEPARATOR . 'archive-proof.pdf');
    }

    private function createOrder(string $reference, string $status): Order
    {
        return (new Order())
            ->setReference($reference)
            ->setStatus($status)
            ->setCustomerFirstName('Alice')
            ->setCustomerLastName('Dupont')
            ->setCustomerEmail('alice@example.test')
            ->setCustomerPhone('0600000000')
            ->setShippingAddress("10 rue du Test\n75000 Paris")
            ->setTotal(1000);
    }
}

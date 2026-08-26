<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\OrderArchive;
use App\Service\CustomizationFileStorage;
use App\Service\OrderArchiveService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class OrderArchiveServiceTest extends TestCase
{
    public function testArchiveAndDeletePersistsArchiveRemovesOrderAndFiles(): void
    {
        $order = (new Order())
            ->setReference('ORD-ARCHIVE-001')
            ->setStatus(Order::STATUS_TERMINE)
            ->setCustomerFirstName('Jane')
            ->setCustomerLastName('Doe')
            ->setCustomerEmail('jane@example.test')
            ->setCustomerPhone('0600000000')
            ->setShippingAddress("1 rue archive\n75000 Paris")
            ->setTotal(1000);

        $item = (new OrderItem())
            ->setProductName('Produit')
            ->setUnitPrice(1000)
            ->setQuantity(1)
            ->setCustomizationFilePath('proof.pdf');
        $order->addItem($item);

        $persistedArchive = null;

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())
            ->method('persist')
            ->with(self::callback(function (object $entity) use (&$persistedArchive): bool {
                if (!$entity instanceof OrderArchive) {
                    return false;
                }

                $persistedArchive = $entity;

                return true;
            }));
        $entityManager->expects(self::once())->method('remove')->with($order);
        $entityManager->expects(self::once())->method('flush');

        $projectDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'kdos-archive-test-' . bin2hex(random_bytes(6));
        $storageDir = $projectDir . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'media' . DIRECTORY_SEPARATOR . 'customizations';
        mkdir($storageDir, 0775, true);
        file_put_contents($storageDir . DIRECTORY_SEPARATOR . 'proof.pdf', 'dummy');

        $fileStorage = new CustomizationFileStorage($projectDir);

        $service = new OrderArchiveService($entityManager, $fileStorage);
        $service->archiveAndDelete($order, 'admin@example.test', 'Suppression test');

        self::assertFileDoesNotExist($storageDir . DIRECTORY_SEPARATOR . 'proof.pdf');

        self::assertInstanceOf(OrderArchive::class, $persistedArchive);
        self::assertSame('ORD-ARCHIVE-001', $persistedArchive->getReference());
        self::assertSame(Order::STATUS_TERMINE, $persistedArchive->getStatus());
        self::assertSame('admin@example.test', $persistedArchive->getArchivedBy());
        self::assertSame('Suppression test', $persistedArchive->getReason());

        $payload = $persistedArchive->getPayload();
        self::assertSame('Jane', $payload['customerFirstName']);
        self::assertCount(1, $payload['items']);
        self::assertSame('proof.pdf', $payload['items'][0]['customizationFilePath']);
    }
}

<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Form\CheckoutOrderType;
use App\Repository\OrderRepository;
use App\Repository\ProductOptionValueRepository;
use App\Repository\ProductRepository;
use App\Service\CartService;
use App\Service\OrderMailer;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/commande', name: 'app_checkout_')]
final class CheckoutController extends AbstractController
{
    #[Route('/', name: 'form', methods: ['GET', 'POST'])]
    public function form(
        Request $request,
        CartService $cartService,
        OrderRepository $orderRepository,
        ProductRepository $productRepository,
        ProductOptionValueRepository $optionValueRepository,
        EntityManagerInterface $entityManager,
        OrderMailer $orderMailer,
        LoggerInterface $logger,
    ): Response {
        $lines = $cartService->getLines();
        if ($lines === []) {
            $this->addFlash('warning', 'Votre panier est vide.');

            return $this->redirectToRoute('app_cart_index');
        }

        $productIds = array_filter(array_map(static fn (array $l) => (int) ($l['productId'] ?? 0), $lines));
        $products = $productRepository->findBy(['id' => $productIds]);
        $baseProductPriceMap = [];
        foreach ($products as $product) {
            $baseProductPriceMap[(int) $product->getId()] = $product->getPrice();
        }

        $order = new Order();
        $form = $this->createForm(CheckoutOrderType::class, $order);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $order->setReference($this->generateReference($orderRepository));

            // Determine status: devis if any line has an image or a special request.
            $isDevis = false;
            foreach ($lines as $line) {
                if (($line['customizationFilePath'] ?? null) !== null || ($line['specialRequest'] ?? null) !== null) {
                    $isDevis = true;
                    break;
                }
            }
            $order->setStatus($isDevis ? Order::STATUS_EN_ATTENTE_DEVIS : Order::STATUS_A_CONFIRMER);

            $total = 0;
            foreach ($lines as $line) {
                $productId = (int) ($line['productId'] ?? 0);
                $basePrice = $baseProductPriceMap[$productId] ?? (int) ($line['unitPrice'] ?? 0);

                // Re-verify option prices server-side at order time.
                $optionValueIds = array_filter(array_map('intval', (array) ($line['optionValueIds'] ?? [])));
                $priceAdjustment = 0;
                if ($optionValueIds !== []) {
                    $optionValues = $optionValueRepository->findByIds($optionValueIds);
                    foreach ($optionValues as $optionValue) {
                        if ($optionValue->getGroup()?->getProduct()?->getId() === $productId && $optionValue->isActive()) {
                            $priceAdjustment += $optionValue->getPriceAdjustment();
                        }
                    }
                }

                $unitPrice = $basePrice + $priceAdjustment;

                $item = (new OrderItem())
                    ->setProductName((string) ($line['productName'] ?? 'Produit'))
                    ->setUnitPrice($unitPrice)
                    ->setQuantity((int) ($line['quantity'] ?? 1))
                    ->setCustomizationText($line['customizationText'] ?? null)
                    ->setCustomizationFilePath($line['customizationFilePath'] ?? null)
                    ->setOptionsSummary($line['optionsSummary'] ?? null)
                    ->setSpecialRequest($line['specialRequest'] ?? null);

                $order->addItem($item);
                $total += $item->getSubtotal();
            }

            $order->setTotal($total);

            $entityManager->persist($order);
            $entityManager->flush();

            try {
                $orderMailer->sendOrderReceived($order);
            } catch (\Throwable $e) {
                $logger->error('Checkout email failed for order {ref}: {msg}', [
                    'ref' => $order->getReference(),
                    'msg' => $e->getMessage(),
                ]);
            }

            $cartService->clear();

            return $this->redirectToRoute('app_checkout_confirmation', ['reference' => $order->getReference()]);
        }

        $totalCents = 0;
        foreach ($lines as $line) {
            $totalCents += (int) ($line['unitPrice'] ?? 0) * (int) ($line['quantity'] ?? 1);
        }

        return $this->render('checkout/form.html.twig', [
            'form' => $form,
            'lines' => $lines,
            'totalCents' => $totalCents,
        ]);
    }

    #[Route('/confirmation/{reference}', name: 'confirmation', methods: ['GET'])]
    public function confirmation(string $reference, OrderRepository $orderRepository): Response
    {
        $order = $orderRepository->findOneBy(['reference' => $reference]);

        return $this->render('checkout/confirmation.html.twig', [
            'reference' => $reference,
            'isDevis' => $order !== null && $order->getStatus() === Order::STATUS_EN_ATTENTE_DEVIS,
        ]);
    }

    private function generateReference(OrderRepository $orderRepository): string
    {
        do {
            $reference = 'ORD-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
        } while ($orderRepository->existsReference($reference));

        return $reference;
    }
}

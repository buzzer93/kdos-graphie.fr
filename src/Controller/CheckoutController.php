<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Form\CheckoutOrderType;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use App\Service\CartService;
use App\Service\OrderMailer;
use Doctrine\ORM\EntityManagerInterface;
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
        EntityManagerInterface $entityManager,
        OrderMailer $orderMailer,
    ): Response {
        $lines = $cartService->getLines();
        if ($lines === []) {
            $this->addFlash('warning', 'Votre panier est vide.');

            return $this->redirectToRoute('app_cart_index');
        }

        $productIds = array_filter(array_map(static fn (array $l) => (int) ($l['productId'] ?? 0), $lines));
        $products = $productRepository->findBy(['id' => $productIds]);
        $priceMap = [];
        foreach ($products as $product) {
            $priceMap[(int) $product->getId()] = $product->getPrice();
        }

        $order = new Order();
        $form = $this->createForm(CheckoutOrderType::class, $order);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $order->setReference($this->generateReference($orderRepository));
            $order->setStatus(Order::STATUS_A_CONFIRMER);

            $total = 0;
            foreach ($lines as $line) {
                $productId = (int) ($line['productId'] ?? 0);
                $unitPrice = $priceMap[$productId] ?? (int) ($line['unitPrice'] ?? 0);

                $item = (new OrderItem())
                    ->setProductName((string) ($line['productName'] ?? 'Produit'))
                    ->setUnitPrice($unitPrice)
                    ->setQuantity((int) ($line['quantity'] ?? 1))
                    ->setCustomizationText($line['customizationText'] ?? null)
                    ->setCustomizationFilePath($line['customizationFilePath'] ?? null);

                $order->addItem($item);
                $total += $item->getSubtotal();
            }

            $order->setTotal($total);

            $entityManager->persist($order);
            $entityManager->flush();

            $orderMailer->sendOrderReceived($order);

            $cartService->clear();

            return $this->redirectToRoute('app_checkout_confirmation', ['reference' => $order->getReference()]);
        }

        $totalCents = 0;
        foreach ($lines as $line) {
            $productId = (int) ($line['productId'] ?? 0);
            $unitPrice = $priceMap[$productId] ?? (int) ($line['unitPrice'] ?? 0);
            $totalCents += $unitPrice * (int) ($line['quantity'] ?? 1);
        }

        return $this->render('checkout/form.html.twig', [
            'form' => $form,
            'lines' => $lines,
            'totalCents' => $totalCents,
        ]);
    }

    #[Route('/confirmation/{reference}', name: 'confirmation', methods: ['GET'])]
    public function confirmation(string $reference): Response
    {
        return $this->render('checkout/confirmation.html.twig', [
            'reference' => $reference,
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

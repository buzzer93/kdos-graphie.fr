<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Product;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

final class CartService
{
    private const SESSION_KEY = 'cart';

    public function __construct(private readonly RequestStack $requestStack)
    {
    }

    /** @return list<array{id: string, productId: int, productName: string, unitPrice: int, quantity: int, customizationText: ?string, customizationFilePath: ?string}> */
    public function getLines(): array
    {
        $cart = $this->getCart();

        return $cart['lines'] ?? [];
    }

    public function addLine(Product $product, int $quantity, ?string $customizationText, ?string $customizationFilePath): void
    {
        $cart = $this->getCart();
        $lines = $cart['lines'] ?? [];

        $lines[] = [
            'id' => bin2hex(random_bytes(8)),
            'productId' => (int) $product->getId(),
            'productName' => $product->getName(),
            'unitPrice' => $product->getPrice(),
            'quantity' => max(1, $quantity),
            'customizationText' => $customizationText,
            'customizationFilePath' => $customizationFilePath,
        ];

        $cart['lines'] = $lines;
        $this->saveCart($cart);
    }

    public function updateQuantity(string $lineId, int $quantity): void
    {
        $cart = $this->getCart();
        $lines = $cart['lines'] ?? [];

        foreach ($lines as &$line) {
            if (($line['id'] ?? '') === $lineId) {
                $line['quantity'] = max(1, $quantity);
                break;
            }
        }

        $cart['lines'] = $lines;
        $this->saveCart($cart);
    }

    /** @return array{id: string, productId: int, productName: string, unitPrice: int, quantity: int, customizationText: ?string, customizationFilePath: ?string}|null */
    public function removeLine(string $lineId): ?array
    {
        $cart = $this->getCart();
        $lines = $cart['lines'] ?? [];
        $removed = null;

        $filtered = [];
        foreach ($lines as $line) {
            if (($line['id'] ?? '') === $lineId) {
                $removed = $line;
                continue;
            }

            $filtered[] = $line;
        }

        $cart['lines'] = $filtered;
        $this->saveCart($cart);

        return $removed;
    }

    public function clear(): void
    {
        $this->saveCart(['lines' => []]);
    }

    public function getItemsCount(): int
    {
        $count = 0;

        foreach ($this->getLines() as $line) {
            $count += (int) ($line['quantity'] ?? 0);
        }

        return $count;
    }

    public function getTotalCents(): int
    {
        $total = 0;

        foreach ($this->getLines() as $line) {
            $total += ((int) ($line['unitPrice'] ?? 0)) * ((int) ($line['quantity'] ?? 0));
        }

        return $total;
    }

    /** @return array{lines: list<array{id: string, productId: int, productName: string, unitPrice: int, quantity: int, customizationText: ?string, customizationFilePath: ?string}>} */
    private function getCart(): array
    {
        $session = $this->getSession();

        if (!$session->has(self::SESSION_KEY)) {
            return ['lines' => []];
        }

        $cart = $session->get(self::SESSION_KEY);

        if (!is_array($cart)) {
            return ['lines' => []];
        }

        return $cart;
    }

    private function saveCart(array $cart): void
    {
        $this->getSession()->set(self::SESSION_KEY, $cart);
    }

    private function getSession(): SessionInterface
    {
        $request = $this->requestStack->getCurrentRequest();

        if ($request === null) {
            throw new \RuntimeException('Aucune requete active pour manipuler le panier session.');
        }

        return $request->getSession();
    }
}

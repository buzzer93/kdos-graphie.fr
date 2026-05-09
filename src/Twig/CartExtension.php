<?php

declare(strict_types=1);

namespace App\Twig;

use App\Service\CartService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class CartExtension extends AbstractExtension
{
    public function __construct(private readonly CartService $cartService)
    {
    }

    /** @return list<TwigFunction> */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('cart_count', $this->cartCount(...)),
        ];
    }

    public function cartCount(): int
    {
        return $this->cartService->getItemsCount();
    }
}

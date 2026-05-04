<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use Symfony\Component\String\Slugger\SluggerInterface;

class SlugGenerator
{
    public function __construct(
        private readonly SluggerInterface $slugger,
        private readonly CategoryRepository $categoryRepository,
        private readonly ProductRepository $productRepository,
    ) {
    }

    public function generateCategorySlug(string $value, ?int $ignoreId = null): string
    {
        return $this->generateUnique($value, fn (string $slug): bool => $this->categoryRepository->existsSlug($slug, $ignoreId));
    }

    public function generateProductSlug(string $value, ?int $ignoreId = null): string
    {
        return $this->generateUnique($value, fn (string $slug): bool => $this->productRepository->existsSlug($slug, $ignoreId));
    }

    /**
     * @param \Closure(string): bool $exists
     */
    private function generateUnique(string $value, \Closure $exists): string
    {
        $base = strtolower((string) $this->slugger->slug($value));
        $base = $base !== '' ? $base : 'item';

        $slug = $base;
        $suffix = 2;

        while ($exists($slug)) {
            $slug = $base . '-' . $suffix;
            ++$suffix;
        }

        return $slug;
    }
}

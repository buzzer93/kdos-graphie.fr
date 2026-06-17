<?php

namespace App\Repository;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Product>
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    /**
     * @return array{items: list<Product>, total: int, pages: int}
     */
    public function paginateAdminList(?string $search, ?bool $isVisible, ?int $categoryId, int $page, int $perPage = 12): array
    {
        $page = max(1, $page);

        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.category', 'c')
            ->addSelect('c')
            ->orderBy('p.createdAt', 'DESC');

        if ($search !== null && $search !== '') {
            $qb
                ->andWhere('p.name LIKE :search OR p.slug LIKE :search')
                ->setParameter('search', '%' . trim($search) . '%');
        }

        if ($isVisible !== null) {
            $qb
                ->andWhere('p.isVisible = :isVisible')
                ->setParameter('isVisible', $isVisible);
        }

        if ($categoryId !== null) {
            $qb
                ->andWhere('c.id = :categoryId')
                ->setParameter('categoryId', $categoryId);
        }

        $countQb = clone $qb;
        $total = (int) $countQb
            ->select('COUNT(p.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $pages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $pages);

        $items = $qb
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->getQuery()
            ->getResult();

        return [
            'items' => $items,
            'total' => $total,
            'pages' => $pages,
        ];
    }

    /**
     * @return array{items: list<Product>, total: int, pages: int}
     */
    public function paginateVisibleForCatalog(?string $search, ?string $categorySlug, int $page, int $perPage = 12): array
    {
        $page = max(1, $page);

        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.category', 'c')
            ->addSelect('c')
            ->andWhere('p.isVisible = :isVisible')
            ->setParameter('isVisible', true)
            ->orderBy('p.createdAt', 'DESC');

        if ($search !== null && $search !== '') {
            $qb
                ->andWhere('p.name LIKE :search')
                ->setParameter('search', '%' . trim($search) . '%');
        }

        if ($categorySlug !== null && $categorySlug !== '') {
            $qb
                ->andWhere('c.slug = :categorySlug')
                ->setParameter('categorySlug', $categorySlug);
        }

        $countQb = clone $qb;
        $total = (int) $countQb
            ->select('COUNT(p.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $pages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $pages);

        $items = $qb
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->getQuery()
            ->getResult();

        return [
            'items' => $items,
            'total' => $total,
            'pages' => $pages,
        ];
    }

    public function existsSlug(string $slug, ?int $ignoreId = null): bool
    {
        $qb = $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('p.slug = :slug')
            ->setParameter('slug', $slug);

        if ($ignoreId !== null) {
            $qb
                ->andWhere('p.id != :ignoreId')
                ->setParameter('ignoreId', $ignoreId);
        }

        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }

    public function countVisible(): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('p.isVisible = :isVisible')
            ->setParameter('isVisible', true)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return array<int, list<Product>>
     */
    public function findVisibleGroupedByCategory(): array
    {
        /** @var list<Product> $products */
        $products = $this->createQueryBuilder('p')
            ->leftJoin('p.category', 'c')
            ->addSelect('c')
            ->andWhere('p.isVisible = :isVisible')
            ->setParameter('isVisible', true)
            ->orderBy('c.name', 'ASC')
            ->addOrderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();

        $grouped = [];
        foreach ($products as $product) {
            $catId = $product->getCategory()?->getId() ?? 0;
            $grouped[$catId][] = $product;
        }

        return $grouped;
    }

    public function findVisibleBySlug(string $slug): ?Product
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.category', 'c')
            ->addSelect('c')
            ->andWhere('p.slug = :slug')
            ->andWhere('p.isVisible = :isVisible')
            ->setParameter('slug', $slug)
            ->setParameter('isVisible', true)
            ->getQuery()
            ->getOneOrNullResult();
    }
}

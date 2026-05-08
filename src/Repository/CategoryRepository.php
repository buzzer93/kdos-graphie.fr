<?php

namespace App\Repository;

use App\Entity\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Category>
 */
class CategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Category::class);
    }

    /**
     * @return array{items: list<Category>, total: int, pages: int}
     */
    public function paginateAdminList(?string $search, ?bool $isVisible, int $page, int $perPage = 12): array
    {
        $page = max(1, $page);

        $qb = $this->createQueryBuilder('c')
            ->orderBy('c.sortOrder', 'ASC')
            ->addOrderBy('c.id', 'ASC');

        if ($search !== null && $search !== '') {
            $qb
                ->andWhere('c.name LIKE :search OR c.slug LIKE :search')
                ->setParameter('search', '%' . trim($search) . '%');
        }

        if ($isVisible !== null) {
            $qb
                ->andWhere('c.isVisible = :isVisible')
                ->setParameter('isVisible', $isVisible);
        }

        $countQb = clone $qb;
        $total = (int) $countQb
            ->select('COUNT(c.id)')
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

    /** @return list<Category> */
    public function findAllOrderedBySortOrder(): array
    {
        return $this->createQueryBuilder('c')
            ->orderBy('c.sortOrder', 'ASC')
            ->addOrderBy('c.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** @return list<Category> */
    public function findVisibleOrderedByName(): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.isVisible = :isVisible')
            ->setParameter('isVisible', true)
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function existsSlug(string $slug, ?int $ignoreId = null): bool
    {
        $qb = $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->andWhere('c.slug = :slug')
            ->setParameter('slug', $slug);

        if ($ignoreId !== null) {
            $qb
                ->andWhere('c.id != :ignoreId')
                ->setParameter('ignoreId', $ignoreId);
        }

        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }
}

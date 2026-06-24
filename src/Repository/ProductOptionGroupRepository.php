<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Product;
use App\Entity\ProductOptionGroup;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProductOptionGroup>
 */
class ProductOptionGroupRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductOptionGroup::class);
    }

    /** @return ProductOptionGroup[] */
    public function findByProductOrdered(Product $product): array
    {
        return $this->createQueryBuilder('g')
            ->where('g.product = :product')
            ->setParameter('product', $product)
            ->orderBy('g.sortOrder', 'ASC')
            ->addOrderBy('g.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
}

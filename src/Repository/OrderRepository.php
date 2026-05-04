<?php

namespace App\Repository;

use App\Entity\Order;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Order>
 */
class OrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

    /**
     * @return array{items: list<Order>, total: int, pages: int}
     */
    public function paginateAdminList(?string $search, ?string $status, int $page, int $perPage = 12): array
    {
        $page = max(1, $page);

        $qb = $this->createQueryBuilder('o')
            ->leftJoin('o.items', 'i')
            ->addSelect('i')
            ->orderBy('o.createdAt', 'DESC');

        if ($search !== null && $search !== '') {
            $qb
                ->andWhere('o.reference LIKE :search OR o.customerEmail LIKE :search OR o.customerFirstName LIKE :search OR o.customerLastName LIKE :search')
                ->setParameter('search', '%' . trim($search) . '%');
        }

        if ($status !== null && $status !== '') {
            $qb
                ->andWhere('o.status = :status')
                ->setParameter('status', $status);
        }

        $countQb = clone $qb;
        $total = (int) $countQb
            ->select('COUNT(DISTINCT o.id)')
            ->resetDQLPart('orderBy')
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

    public function existsReference(string $reference, ?int $ignoreId = null): bool
    {
        $qb = $this->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->andWhere('o.reference = :reference')
            ->setParameter('reference', $reference);

        if ($ignoreId !== null) {
            $qb
                ->andWhere('o.id != :ignoreId')
                ->setParameter('ignoreId', $ignoreId);
        }

        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }

    public function getRevenueCentsExcludingRejected(): int
    {
        return (int) $this->createQueryBuilder('o')
            ->select('COALESCE(SUM(o.total), 0)')
            ->andWhere('o.status NOT IN (:excludedStatuses)')
            ->setParameter('excludedStatuses', [Order::STATUS_REFUSE, Order::STATUS_ANNULE])
            ->getQuery()
            ->getSingleScalarResult();
    }
}

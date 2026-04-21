<?php

declare(strict_types=1);

namespace App\Domain\Inventory\Repository;

use App\Domain\Catalog\Entity\CatalogItemId;
use App\Domain\Inventory\Entity\InventoryItem;
use App\Infrastructure\Catalog\Doctrine\CatalogItemIdType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<InventoryItem>
 */
final class InventoryItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, InventoryItem::class);
    }

    /**
     * @return list<InventoryItem>
     */
    public function findAllOrderedById(): array
    {
        /** @var list<InventoryItem> */
        return $this->createQueryBuilder('i')
            ->orderBy('i.inventoryItemId', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function save(InventoryItem $item): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($item);
        $entityManager->flush();
    }

    public function remove(InventoryItem $item): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->remove($item);
        $entityManager->flush();
    }

    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }

    public function sumQuantityForCatalogItem(CatalogItemId $catalogItemId): string
    {
        $queryBuilder = $this->createQueryBuilder('i')
            ->select('SUM(i.quantity)')
            ->andWhere('i.catalogItem = :cid')
            ->setParameter('cid', $catalogItemId, CatalogItemIdType::NAME);
        $raw = $queryBuilder->getQuery()->getSingleScalarResult();
        if (null === $raw) {
            return '0';
        }

        return is_numeric($raw) ? (string) $raw : '0';
    }
}

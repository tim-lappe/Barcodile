<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Repository;

use App\Inventory\Domain\Entity\InventoryItem;
use App\Inventory\Domain\ValueObject\InventoryItemCode;
use App\SharedKernel\Domain\Id\CatalogItemId;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use LogicException;

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

    public function findOneByPublicCode(InventoryItemCode $publicCode): ?InventoryItem
    {
        return $this->findOneBy(['publicCode' => $publicCode]);
    }

    public function countForCatalogItem(CatalogItemId $catalogItemId): int
    {
        $queryBuilder = $this->createQueryBuilder('i')
            ->select('COUNT(i.inventoryItemId)')
            ->andWhere('i.catalogItemId = :cid')
            ->setParameter('cid', $catalogItemId, CatalogItemId::DOCTRINE_TYPE_NAME);
        $raw = $queryBuilder->getQuery()->getSingleScalarResult();

        return is_numeric($raw) ? (int) $raw : 0;
    }

    public function allocateNextPublicCode(): InventoryItemCode
    {
        for ($attempt = 0; $attempt < 64; ++$attempt) {
            $code = new InventoryItemCode((string) random_int(10_000, 99_999));
            if (!$this->publicCodeIsTaken($code)) {
                return $code;
            }
        }

        throw new LogicException('Could not allocate a unique public code.');
    }

    private function publicCodeIsTaken(InventoryItemCode $code): bool
    {
        $raw = $this->createQueryBuilder('i')
            ->select('COUNT(i.inventoryItemId)')
            ->andWhere('i.publicCode = :code')
            ->setParameter('code', $code, 'inventory_item_code')
            ->getQuery()
            ->getSingleScalarResult();

        return ((int) $raw) > 0;
    }
}

<?php

declare(strict_types=1);

namespace App\Domain\Inventory\Repository;

use App\Domain\Inventory\Entity\InventoryItem;
use App\Domain\Shared\Id\CatalogItemId;
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

    public function findOneByPublicCode(string $publicCode): ?InventoryItem
    {
        return $this->findOneBy(['publicCode' => $publicCode]);
    }

    public function countForCatalogItem(CatalogItemId $catalogItemId): int
    {
        $queryBuilder = $this->createQueryBuilder('i')
            ->select('COUNT(i.inventoryItemId)')
            ->andWhere('i.catalogItem = :cid')
            ->setParameter('cid', $catalogItemId, CatalogItemId::DOCTRINE_TYPE_NAME);
        $raw = $queryBuilder->getQuery()->getSingleScalarResult();

        return is_numeric($raw) ? (int) $raw : 0;
    }

    public function allocateNextPublicCode(): string
    {
        for ($attempt = 0; $attempt < 64; ++$attempt) {
            $code = (string) random_int(10_000, 99_999);
            if (!$this->publicCodeIsTaken($code)) {
                return $code;
            }
        }

        throw new LogicException('Could not allocate a unique public code.');
    }

    private function publicCodeIsTaken(string $code): bool
    {
        $raw = $this->createQueryBuilder('i')
            ->select('COUNT(i.inventoryItemId)')
            ->andWhere('i.publicCode = :code')
            ->setParameter('code', $code)
            ->getQuery()
            ->getSingleScalarResult();

        return ((int) $raw) > 0;
    }
}

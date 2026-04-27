<?php

declare(strict_types=1);

namespace App\Catalog\Domain\Repository;

use App\Catalog\Domain\Entity\CatalogItem;
use App\SharedKernel\Domain\Id\CatalogItemId;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CatalogItem>
 */
final class CatalogItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CatalogItem::class);
    }

    /**
     * @return list<CatalogItem>
     */
    public function findPagedByNameOrder(int $offset, int $limit, string $orderDir, ?string $nameContains): array
    {
        $queryBuilder = $this->createQueryBuilder('c');
        if (null !== $nameContains && '' !== trim($nameContains)) {
            $queryBuilder->andWhere('LOWER(c.name) LIKE :q')
                ->setParameter('q', '%'.mb_strtolower(trim($nameContains)).'%');
        }
        $dir = 'DESC' === strtoupper($orderDir) ? 'DESC' : 'ASC';

        /** @var list<CatalogItem> */
        return $queryBuilder->orderBy('c.name', $dir)
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function save(CatalogItem $item): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($item);
        $entityManager->flush();
    }

    public function remove(CatalogItem $item): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->remove($item);
        $entityManager->flush();
    }

    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }

    public function findOneByBarcodeCodeAndTypeCaseInsensitive(string $code, string $type): ?CatalogItem
    {
        $result = $this->createQueryBuilder('c')
            ->andWhere('c.barcode.code = :code')
            ->andWhere('LOWER(c.barcode.type) = LOWER(:type)')
            ->setParameter('code', $code)
            ->setParameter('type', $type)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $result instanceof CatalogItem ? $result : null;
    }

    public function findWithCatalogItemImage(CatalogItemId $catalogItemId): ?CatalogItem
    {
        $result = $this->createQueryBuilder('c')
            ->leftJoin('c.catalogItemImage', 'img')->addSelect('img')
            ->andWhere('c.catalogItemId = :id')
            ->setParameter('id', $catalogItemId, 'catalog_item_id')
            ->getQuery()
            ->getOneOrNullResult();

        return $result instanceof CatalogItem ? $result : null;
    }
}

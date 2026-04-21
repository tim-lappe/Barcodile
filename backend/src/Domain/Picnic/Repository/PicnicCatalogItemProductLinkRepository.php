<?php

declare(strict_types=1);

namespace App\Domain\Picnic\Repository;

use App\Domain\Catalog\Entity\CatalogItemId;
use App\Domain\Picnic\Entity\PicnicCatalogItemProductLink;
use App\Infrastructure\Catalog\Doctrine\CatalogItemIdType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PicnicCatalogItemProductLink>
 */
final class PicnicCatalogItemProductLinkRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PicnicCatalogItemProductLink::class);
    }

    public function findOneByCatalogItemId(CatalogItemId $catalogItemId): ?PicnicCatalogItemProductLink
    {
        $result = $this->createQueryBuilder('l')
            ->andWhere('l.catalogItem = :id')
            ->setParameter('id', $catalogItemId, CatalogItemIdType::NAME)
            ->getQuery()
            ->getOneOrNullResult();

        return $result instanceof PicnicCatalogItemProductLink ? $result : null;
    }

    /**
     * @param list<CatalogItemId> $catalogItemIds
     *
     * @return array<string, string>
     */
    public function mapProductIdByCatalogItemId(array $catalogItemIds): array
    {
        if ([] === $catalogItemIds) {
            return [];
        }
        /** @var list<PicnicCatalogItemProductLink> $rows */
        $rows = $this->createQueryBuilder('l')
            ->where('l.catalogItem IN (:ids)')
            ->setParameter('ids', $catalogItemIds)
            ->getQuery()
            ->getResult();
        $out = [];
        foreach ($rows as $row) {
            $out[(string) $row->getCatalogItem()->getId()] = $row->getProductId();
        }

        return $out;
    }
}

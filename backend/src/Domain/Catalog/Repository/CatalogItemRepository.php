<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Repository;

use App\Domain\Catalog\Entity\CatalogItem;
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
}

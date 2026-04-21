<?php

declare(strict_types=1);

namespace App\Domain\Cart\Repository;

use App\Domain\Cart\Entity\ShoppingCart;
use App\Domain\Cart\Entity\ShoppingCartId;
use App\Domain\Cart\Entity\ShoppingCartLine;
use App\Domain\Cart\Entity\ShoppingCartLineId;
use App\Domain\Catalog\Entity\CatalogItemId;
use App\Infrastructure\Cart\Doctrine\ShoppingCartIdType;
use App\Infrastructure\Catalog\Doctrine\CatalogItemIdType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ShoppingCart>
 */
final class ShoppingCartRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ShoppingCart::class);
    }

    /**
     * @return list<ShoppingCart>
     */
    public function findPagedByCreatedAtDesc(int $offset, int $limit): array
    {
        /** @var list<ShoppingCart> */
        return $this->createQueryBuilder('c')
            ->orderBy('c.createdAt', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function save(ShoppingCart $cart): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($cart);
        $entityManager->flush();
    }

    public function remove(ShoppingCart $cart): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->remove($cart);
        $entityManager->flush();
    }

    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }

    public function findLineById(ShoppingCartLineId $lineId): ?ShoppingCartLine
    {
        $line = $this->getEntityManager()->find(ShoppingCartLine::class, $lineId);

        return $line instanceof ShoppingCartLine ? $line : null;
    }

    public function findLineByCartAndCatalogItem(ShoppingCartId $shoppingCartId, CatalogItemId $catalogItemId): ?ShoppingCartLine
    {
        $result = $this->getEntityManager()->createQueryBuilder()
            ->select('l')
            ->from(ShoppingCartLine::class, 'l')
            ->join('l.shoppingCart', 'c')
            ->andWhere('c.id = :cid')
            ->andWhere('l.catalogItem = :item')
            ->setParameter('cid', $shoppingCartId, ShoppingCartIdType::NAME)
            ->setParameter('item', $catalogItemId, CatalogItemIdType::NAME)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $result instanceof ShoppingCartLine ? $result : null;
    }

    /**
     * @return list<ShoppingCartLine>
     */
    public function findLinesPaged(int $offset, int $limit, ?ShoppingCartId $shoppingCartId): array
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder()
            ->select('l')
            ->from(ShoppingCartLine::class, 'l')
            ->orderBy('l.createdAt', 'ASC');
        if (null !== $shoppingCartId) {
            $queryBuilder->andWhere('l.shoppingCart = :cid')
                ->setParameter('cid', $shoppingCartId, ShoppingCartIdType::NAME);
        }

        /** @var list<ShoppingCartLine> */
        return $queryBuilder->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}

<?php

declare(strict_types=1);

namespace App\Domain\Inventory\Repository;

use App\Domain\Inventory\Entity\CartStockAutomationRule;
use App\Domain\Shared\Id\CartStockAutomationRuleId;
use App\Domain\Shared\Id\CatalogItemId;
use App\Domain\Shared\Id\ShoppingCartId;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CartStockAutomationRule>
 */
final class CartStockAutomationRuleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CartStockAutomationRule::class);
    }

    /**
     * @return list<CartStockAutomationRule>
     */
    public function findEnabledByCatalogItemId(CatalogItemId $catalogItemId): array
    {
        /** @var list<CartStockAutomationRule> */
        return $this->createQueryBuilder('r')
            ->andWhere('r.catalogItem = :cid')
            ->andWhere('r.enabled = true')
            ->setParameter('cid', $catalogItemId, CatalogItemId::DOCTRINE_TYPE_NAME)
            ->orderBy('r.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<CartStockAutomationRule>
     */
    public function findAllByCatalogItemIdOrdered(CatalogItemId $catalogItemId): array
    {
        /** @var list<CartStockAutomationRule> */
        return $this->createQueryBuilder('r')
            ->andWhere('r.catalogItem = :cid')
            ->setParameter('cid', $catalogItemId, CatalogItemId::DOCTRINE_TYPE_NAME)
            ->orderBy('r.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findOneByIdAndCatalogItemId(CartStockAutomationRuleId $ruleId, CatalogItemId $catalogItemId): ?CartStockAutomationRule
    {
        $result = $this->createQueryBuilder('r')
            ->andWhere('r.id = :rid')
            ->andWhere('r.catalogItem = :cid')
            ->setParameter('rid', $ruleId, CartStockAutomationRuleId::DOCTRINE_TYPE_NAME)
            ->setParameter('cid', $catalogItemId, CatalogItemId::DOCTRINE_TYPE_NAME)
            ->getQuery()
            ->getOneOrNullResult();

        return $result instanceof CartStockAutomationRule ? $result : null;
    }

    public function findOneByCatalogItemAndShoppingCart(CatalogItemId $catalogItemId, ShoppingCartId $shoppingCartId): ?CartStockAutomationRule
    {
        $result = $this->createQueryBuilder('r')
            ->andWhere('r.catalogItem = :cid')
            ->andWhere('r.shoppingCart = :sid')
            ->setParameter('cid', $catalogItemId, CatalogItemId::DOCTRINE_TYPE_NAME)
            ->setParameter('sid', $shoppingCartId, ShoppingCartId::DOCTRINE_TYPE_NAME)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $result instanceof CartStockAutomationRule ? $result : null;
    }

    public function save(CartStockAutomationRule $rule): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($rule);
        $entityManager->flush();
    }

    public function remove(CartStockAutomationRule $rule): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->remove($rule);
        $entityManager->flush();
    }

    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }
}

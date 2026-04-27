<?php

declare(strict_types=1);

namespace App\Catalog\Domain\Repository;

use App\Catalog\Domain\Entity\BarcodeLookupProvider;
use App\SharedKernel\Domain\Id\BarcodeLookupProviderId;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<BarcodeLookupProvider>
 */
final class BarcodeLookupProviderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BarcodeLookupProvider::class);
    }

    /**
     * @return list<BarcodeLookupProvider>
     */
    public function findAllOrderedBySortOrder(): array
    {
        return $this->createQueryBuilder('b')
            ->orderBy('b.sortOrder', 'ASC')
            ->addOrderBy('b.barcodeLookupProviderId', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<BarcodeLookupProvider>
     */
    public function findEnabledWithApiKeyOrderedBySortOrder(): array
    {
        return $this->createQueryBuilder('b')
            ->where('b.enabled = :enabled')
            ->andWhere('b.apiKeyCipher IS NOT NULL')
            ->andWhere('b.apiKeyCipher != :empty')
            ->setParameter('enabled', true)
            ->setParameter('empty', '')
            ->orderBy('b.sortOrder', 'ASC')
            ->addOrderBy('b.barcodeLookupProviderId', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findById(BarcodeLookupProviderId $id): ?BarcodeLookupProvider
    {
        return $this->find($id);
    }

    public function nextSortOrder(): int
    {
        $qb = $this->createQueryBuilder('b');
        $qb->select('COALESCE(MAX(b.sortOrder), -1)');
        $max = $qb->getQuery()->getSingleScalarResult();

        return ((int) $max) + 1;
    }

    public function save(BarcodeLookupProvider $provider): void
    {
        $em = $this->getEntityManager();
        $em->persist($provider);
        $em->flush();
    }

    public function remove(BarcodeLookupProvider $provider): void
    {
        $em = $this->getEntityManager();
        $em->remove($provider);
        $em->flush();
    }
}

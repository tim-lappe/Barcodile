<?php

declare(strict_types=1);

namespace App\AI\Domain\Repository;

use App\AI\Domain\Entity\LlmProfile;
use App\SharedKernel\Domain\Id\LlmProfileId;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LlmProfile>
 */
final class LlmProfileRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LlmProfile::class);
    }

    /**
     * @return list<LlmProfile>
     */
    public function findAllOrderedBySortOrder(): array
    {
        /** @var list<LlmProfile> */
        return $this->createQueryBuilder('l')
            ->orderBy('l.sortOrder', 'ASC')
            ->addOrderBy('l.llmProfileId', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findById(LlmProfileId $profileId): ?LlmProfile
    {
        return $this->find($profileId);
    }

    public function nextSortOrder(): int
    {
        $queryBuilder = $this->createQueryBuilder('l');
        $queryBuilder->select('COALESCE(MAX(l.sortOrder), -1)');
        $max = $queryBuilder->getQuery()->getSingleScalarResult();

        return ((int) $max) + 1;
    }

    public function save(LlmProfile $profile): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($profile);
        $entityManager->flush();
    }

    public function remove(LlmProfile $profile): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->remove($profile);
        $entityManager->flush();
    }
}

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

    public function findById(LlmProfileId $id): ?LlmProfile
    {
        return $this->find($id);
    }

    public function nextSortOrder(): int
    {
        $qb = $this->createQueryBuilder('l');
        $qb->select('COALESCE(MAX(l.sortOrder), -1)');
        $max = $qb->getQuery()->getSingleScalarResult();

        return ((int) $max) + 1;
    }

    public function save(LlmProfile $profile): void
    {
        $em = $this->getEntityManager();
        $em->persist($profile);
        $em->flush();
    }

    public function remove(LlmProfile $profile): void
    {
        $em = $this->getEntityManager();
        $em->remove($profile);
        $em->flush();
    }
}

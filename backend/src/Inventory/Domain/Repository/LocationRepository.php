<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Repository;

use App\Inventory\Domain\Entity\Location;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Location>
 */
final class LocationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Location::class);
    }

    /**
     * @return list<Location>
     */
    public function findAllOrderedByName(): array
    {
        /** @var list<Location> */
        return $this->createQueryBuilder('l')
            ->orderBy('l.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function save(Location $location): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($location);
        $entityManager->flush();
    }

    public function remove(Location $location): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->remove($location);
        $entityManager->flush();
    }

    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }
}

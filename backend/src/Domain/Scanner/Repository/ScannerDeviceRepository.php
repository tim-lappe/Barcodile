<?php

declare(strict_types=1);

namespace App\Domain\Scanner\Repository;

use App\Domain\Scanner\Entity\ScannerDevice;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ScannerDevice>
 */
final class ScannerDeviceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ScannerDevice::class);
    }

    /**
     * @return list<ScannerDevice>
     */
    public function findAllOrderedByName(): array
    {
        return $this->createQueryBuilder('s')
            ->orderBy('s.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function save(ScannerDevice $scannerDevice): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($scannerDevice);
        $entityManager->flush();
    }

    public function remove(ScannerDevice $scannerDevice): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->remove($scannerDevice);
        $entityManager->flush();
    }

    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }
}

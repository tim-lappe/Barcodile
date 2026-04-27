<?php

declare(strict_types=1);

namespace App\Scanner\Domain\Repository;

use App\Scanner\Domain\Entity\ScannerDevice;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use LogicException;

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

    public function findByDeviceIdentifier(string $deviceIdentifier): ?ScannerDevice
    {
        $result = $this->createQueryBuilder('s')
            ->where('s.deviceIdentifier = :deviceIdentifier')
            ->setParameter('deviceIdentifier', $deviceIdentifier)
            ->getQuery()
            ->getOneOrNullResult();
        if (null === $result) {
            return null;
        }
        if (!$result instanceof ScannerDevice) {
            throw new LogicException('Expected ScannerDevice from device identifier query.');
        }

        return $result;
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

    public function refresh(ScannerDevice $scannerDevice): void
    {
        $this->getEntityManager()->refresh($scannerDevice);
    }
}

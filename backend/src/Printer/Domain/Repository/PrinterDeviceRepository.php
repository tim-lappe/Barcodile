<?php

declare(strict_types=1);

namespace App\Printer\Domain\Repository;

use App\Printer\Domain\Entity\PrinterDevice;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PrinterDevice>
 */
final class PrinterDeviceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PrinterDevice::class);
    }

    /**
     * @return list<PrinterDevice>
     */
    public function findAllOrderedByName(): array
    {
        return $this->createQueryBuilder('p')
            ->orderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function save(PrinterDevice $printerDevice): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($printerDevice);
        $entityManager->flush();
    }

    public function remove(PrinterDevice $printerDevice): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->remove($printerDevice);
        $entityManager->flush();
    }
}

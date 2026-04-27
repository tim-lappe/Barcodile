<?php

declare(strict_types=1);

namespace App\Printer\Domain\Repository;

use App\Printer\Domain\Entity\PrintedLabel;
use App\SharedKernel\Domain\Id\PrintedLabelId;
use App\SharedKernel\Domain\Id\PrinterDeviceId;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PrintedLabel>
 */
final class PrintedLabelRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PrintedLabel::class);
    }

    /**
     * @return list<PrintedLabel>
     */
    public function findRecentForPrinter(PrinterDeviceId $printerDeviceId, int $limit): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.printerDeviceId = :printerDeviceId')
            ->setParameter('printerDeviceId', $printerDeviceId)
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findForPrinter(PrintedLabelId $printedLabelId, PrinterDeviceId $printerDeviceId): ?PrintedLabel
    {
        $printedLabel = $this->createQueryBuilder('p')
            ->andWhere('p.printedLabelId = :printedLabelId')
            ->andWhere('p.printerDeviceId = :printerDeviceId')
            ->setParameter('printedLabelId', $printedLabelId)
            ->setParameter('printerDeviceId', $printerDeviceId)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$printedLabel instanceof PrintedLabel) {
            return null;
        }

        return $printedLabel;
    }

    public function save(PrintedLabel $printedLabel): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($printedLabel);
        $entityManager->flush();
    }
}

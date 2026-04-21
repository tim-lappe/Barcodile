<?php

declare(strict_types=1);

namespace App\Domain\Picnic\Repository;

use App\Domain\Picnic\Entity\PicnicIntegrationSettings;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PicnicIntegrationSettings>
 */
final class PicnicIntegrationSettingsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PicnicIntegrationSettings::class);
    }

    public function getSingleton(): PicnicIntegrationSettings
    {
        $existing = $this->createQueryBuilder('p')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
        if ($existing instanceof PicnicIntegrationSettings) {
            return $existing;
        }

        $created = new PicnicIntegrationSettings();
        $entityManager = $this->getEntityManager();
        $entityManager->persist($created);
        $entityManager->flush();

        return $created;
    }

    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }
}

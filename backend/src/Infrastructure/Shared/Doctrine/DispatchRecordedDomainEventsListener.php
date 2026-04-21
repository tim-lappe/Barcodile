<?php

declare(strict_types=1);

namespace App\Infrastructure\Shared\Doctrine;

use App\Domain\Shared\RecordsDomainEvents;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Events;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[AsDoctrineListener(event: Events::onFlush)]
#[AsDoctrineListener(event: Events::postFlush)]
final class DispatchRecordedDomainEventsListener
{
    /** @var array<int, RecordsDomainEvents> */
    private array $pendingEntities = [];

    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function onFlush(OnFlushEventArgs $args): void
    {
        $uow = $args->getObjectManager()->getUnitOfWork();
        $entities = [
            ...$uow->getScheduledEntityInsertions(),
            ...$uow->getScheduledEntityUpdates(),
            ...$uow->getScheduledEntityDeletions(),
        ];
        foreach ($entities as $entity) {
            if (!$entity instanceof RecordsDomainEvents) {
                continue;
            }
            $this->pendingEntities[spl_object_id($entity)] = $entity;
        }
    }

    public function postFlush(PostFlushEventArgs $args): void
    {
        $args->getObjectManager();
        if ([] === $this->pendingEntities) {
            return;
        }
        $batch = $this->pendingEntities;
        $this->pendingEntities = [];
        foreach ($batch as $entity) {
            foreach ($entity->pullRecordedDomainEvents() as $domainEvent) {
                $this->eventDispatcher->dispatch($domainEvent);
            }
        }
    }
}

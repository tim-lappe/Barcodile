<?php

declare(strict_types=1);

namespace App\Infrastructure\Shared\Doctrine;

use App\Domain\Shared\Entity\PersistedDomainEvent;
use App\Domain\Shared\Entity\PersistedDomainEventId;
use App\Domain\Shared\RecordsDomainEvents;
use App\Infrastructure\Shared\DomainEvent\DomainEventPersistedPayloadBuilder;
use DateTimeImmutable;
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
        private DomainEventPersistedPayloadBuilder $domainEventPayloadBuilder,
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
        if ([] === $this->pendingEntities) {
            return;
        }
        $batch = $this->pendingEntities;
        $this->pendingEntities = [];
        $objectManager = $args->getObjectManager();
        $wrote = false;
        foreach ($batch as $entity) {
            foreach ($entity->pullRecordedDomainEvents() as $domainEvent) {
                $this->eventDispatcher->dispatch($domainEvent);
                $objectManager->persist(
                    new PersistedDomainEvent(
                        new PersistedDomainEventId(),
                        $this->domainEventPayloadBuilder->build($domainEvent),
                        new DateTimeImmutable(),
                    ),
                );
                $wrote = true;
            }
        }
        if ($wrote) {
            $objectManager->flush();
        }
    }
}

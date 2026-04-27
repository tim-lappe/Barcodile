<?php

declare(strict_types=1);

namespace App\SharedKernel\Infrastructure\Doctrine;

use App\SharedKernel\Domain\Entity\PersistedDomainEvent;
use App\SharedKernel\Domain\Id\PersistedDomainEventId;
use App\SharedKernel\Domain\RecordsDomainEvents;
use App\SharedKernel\Infrastructure\DomainEvent\DomainEventPersistedPayloadBuilder;
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
        private DomainEventPersistedPayloadBuilder $payloadBuilder,
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
        $this->persistDispatchedEvents($args);
    }

    private function persistDispatchedEvents(PostFlushEventArgs $args): void
    {
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
                        $this->payloadBuilder->build($domainEvent),
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

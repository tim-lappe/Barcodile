<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Facade;

use App\SharedKernel\Domain\Entity\PersistedDomainEvent;
use App\SharedKernel\Domain\Repository\PersistedDomainEventRepository;

final readonly class PersistedDomainEventFacade
{
    public function __construct(
        private PersistedDomainEventRepository $eventsRepository,
    ) {
    }

    /**
     * @return list<PersistedDomainEventView>
     */
    public function listRecent(int $limit): array
    {
        return array_map(
            static function (PersistedDomainEvent $row): PersistedDomainEventView {
                $payload = $row->getEventDto();

                return new PersistedDomainEventView(
                    eventId: (string) $row->getId()->toUuid(),
                    eventClass: $payload['eventClass'],
                    data: $payload['data'],
                    createdAt: $row->getCreatedAt()->format(\DATE_ATOM),
                );
            },
            $this->eventsRepository->findLastByCreatedAtDesc($limit),
        );
    }
}

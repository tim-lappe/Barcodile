<?php

declare(strict_types=1);

namespace App\Activity\Application;

use App\Activity\Application\Dto\ActivityListResponse;
use App\Activity\Application\Dto\PersistedDomainEventItemResponse;
use App\SharedKernel\Domain\Entity\PersistedDomainEvent;
use App\SharedKernel\Domain\Repository\PersistedDomainEventRepository;

final readonly class ActivityApplicationService
{
    private const int DEFAULT_LIMIT = 200;

    public function __construct(
        private PersistedDomainEventRepository $events,
    ) {
    }

    public function listRecentPersistedDomainEvents(?int $limit = null): ActivityListResponse
    {
        $rowLimit = $limit ?? self::DEFAULT_LIMIT;
        if ($rowLimit < 1) {
            $rowLimit = self::DEFAULT_LIMIT;
        }
        if ($rowLimit > 200) {
            $rowLimit = 200;
        }
        $items = array_map(
            static function (PersistedDomainEvent $row): PersistedDomainEventItemResponse {
                $payload = $row->getEventDto();

                return new PersistedDomainEventItemResponse(
                    eventId: (string) $row->getId()->toUuid(),
                    eventClass: $payload['eventClass'],
                    data: $payload['data'],
                    createdAt: $row->getCreatedAt()->format(\DATE_ATOM),
                );
            },
            $this->events->findLastByCreatedAtDesc($rowLimit),
        );

        return new ActivityListResponse($items);
    }
}

<?php

declare(strict_types=1);

namespace App\Application\Activity;

use App\Application\Activity\Dto\ActivityListResponse;
use App\Application\Activity\Dto\PersistedDomainEventItemResponse;
use App\Domain\Shared\Entity\PersistedDomainEvent;
use App\Domain\Shared\Repository\PersistedDomainEventRepository;

final readonly class ActivityApplicationService
{
    private const int DEFAULT_LIMIT = 200;

    public function __construct(
        private PersistedDomainEventRepository $eventsRepository,
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
        $rows = $this->eventsRepository->findLastByCreatedAtDesc($rowLimit);
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
            $rows,
        );

        return new ActivityListResponse($items);
    }
}

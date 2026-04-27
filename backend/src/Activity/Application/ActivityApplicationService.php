<?php

declare(strict_types=1);

namespace App\Activity\Application;

use App\Activity\Api\Dto\ActivityListResponse;
use App\Activity\Api\Dto\PersistedDomainEventItemResponse;
use App\SharedKernel\Domain\Facade\PersistedDomainEventFacade;
use App\SharedKernel\Domain\Facade\PersistedDomainEventView;

final readonly class ActivityApplicationService
{
    private const int DEFAULT_LIMIT = 200;

    public function __construct(
        private PersistedDomainEventFacade $events,
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
            static function (PersistedDomainEventView $row): PersistedDomainEventItemResponse {
                return new PersistedDomainEventItemResponse(
                    eventId: $row->eventId,
                    eventClass: $row->eventClass,
                    data: $row->data,
                    createdAt: $row->createdAt,
                );
            },
            $this->events->listRecent($rowLimit),
        );

        return new ActivityListResponse($items);
    }
}

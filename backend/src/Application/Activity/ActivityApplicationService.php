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
        private PersistedDomainEventRepository $persistedDomainEventRepository,
    ) {
    }

    public function listRecentPersistedDomainEvents(?int $limit = null): ActivityListResponse
    {
        $n = $limit ?? self::DEFAULT_LIMIT;
        if ($n < 1) {
            $n = self::DEFAULT_LIMIT;
        }
        if ($n > 200) {
            $n = 200;
        }
        $rows = $this->persistedDomainEventRepository->findLastByCreatedAtDesc($n);
        $items = array_map(
            function (PersistedDomainEvent $row): PersistedDomainEventItemResponse {
                $payload = $row->getEventDto();

                return new PersistedDomainEventItemResponse(
                    (string) $row->getId()->toUuid(),
                    $payload['eventClass'],
                    $payload['data'],
                    $row->getCreatedAt()->format(DATE_ATOM),
                );
            },
            $rows,
        );

        return new ActivityListResponse($items);
    }
}

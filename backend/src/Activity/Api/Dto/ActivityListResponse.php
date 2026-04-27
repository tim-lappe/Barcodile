<?php

declare(strict_types=1);

namespace App\Activity\Api\Dto;

final readonly class ActivityListResponse
{
    /**
     * @param list<PersistedDomainEventItemResponse> $items
     */
    public function __construct(
        public array $items,
    ) {
    }
}

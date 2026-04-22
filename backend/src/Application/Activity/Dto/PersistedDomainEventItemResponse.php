<?php

declare(strict_types=1);

namespace App\Application\Activity\Dto;

final readonly class PersistedDomainEventItemResponse
{
    public function __construct(
        public string $id,
        public string $eventClass,
        public mixed $data,
        public string $createdAt,
    ) {
    }
}

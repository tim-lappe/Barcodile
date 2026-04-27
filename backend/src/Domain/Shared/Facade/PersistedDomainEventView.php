<?php

declare(strict_types=1);

namespace App\Domain\Shared\Facade;

final readonly class PersistedDomainEventView
{
    public function __construct(
        public string $eventId,
        public string $eventClass,
        public mixed $data,
        public string $createdAt,
    ) {
    }
}

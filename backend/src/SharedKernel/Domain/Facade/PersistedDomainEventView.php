<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Facade;

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

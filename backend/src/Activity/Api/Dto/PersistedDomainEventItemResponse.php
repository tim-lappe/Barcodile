<?php

declare(strict_types=1);

namespace App\Activity\Api\Dto;

use Symfony\Component\Serializer\Attribute\SerializedName;

final readonly class PersistedDomainEventItemResponse
{
    public function __construct(
        #[SerializedName('id')]
        public string $eventId,
        public string $eventClass,
        public mixed $data,
        public string $createdAt,
    ) {
    }
}

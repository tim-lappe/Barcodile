<?php

declare(strict_types=1);

namespace App\Application\Inventory\Dto;

final readonly class PatchInventoryItemRequest
{
    public function __construct(
        public string $catalogItem,
        public ?string $location,
        public ?string $expirationDate,
    ) {
    }
}

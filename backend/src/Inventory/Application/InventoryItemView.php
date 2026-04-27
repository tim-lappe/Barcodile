<?php

declare(strict_types=1);

namespace App\Inventory\Application;

final readonly class InventoryItemView
{
    public function __construct(
        public string $resourceId,
        public string $publicCode,
        public string $catalogItemId,
        public ?LocationView $location,
        public ?string $expirationDate,
        public string $createdAt,
    ) {
    }
}

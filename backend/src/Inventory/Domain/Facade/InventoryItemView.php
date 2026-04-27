<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Facade;

use App\Catalog\Domain\Facade\CatalogItemView;

final readonly class InventoryItemView
{
    public function __construct(
        public string $resourceId,
        public string $publicCode,
        public CatalogItemView $catalogItem,
        public ?LocationView $location,
        public ?string $expirationDate,
        public string $createdAt,
    ) {
    }
}

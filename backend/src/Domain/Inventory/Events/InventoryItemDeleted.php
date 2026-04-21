<?php

declare(strict_types=1);

namespace App\Domain\Inventory\Events;

use App\Domain\Catalog\Entity\CatalogItemId;
use App\Domain\Inventory\Entity\InventoryItemId;

final readonly class InventoryItemDeleted
{
    public function __construct(
        public InventoryItemId $inventoryItemId,
        public CatalogItemId $catalogItemId,
        public string $lastQuantity,
    ) {
    }
}

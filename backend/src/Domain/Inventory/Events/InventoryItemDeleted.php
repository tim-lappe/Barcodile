<?php

declare(strict_types=1);

namespace App\Domain\Inventory\Events;

use App\Domain\Shared\Id\CatalogItemId;
use App\Domain\Shared\Id\InventoryItemId;

final readonly class InventoryItemDeleted
{
    public function __construct(
        public InventoryItemId $inventoryItemId,
        public CatalogItemId $catalogItemId,
    ) {
    }
}

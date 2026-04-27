<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Events;

use App\SharedKernel\Domain\Id\CatalogItemId;
use App\SharedKernel\Domain\Id\InventoryItemId;

final readonly class InventoryItemDeleted
{
    public function __construct(
        public InventoryItemId $inventoryItemId,
        public CatalogItemId $catalogItemId,
    ) {
    }
}

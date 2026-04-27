<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Events;

use App\Inventory\Domain\Entity\InventoryItem;
use App\SharedKernel\Domain\Id\CatalogItemId;

final readonly class InventoryItemCatalogItemChanged
{
    public function __construct(
        public InventoryItem $inventoryItem,
        public ?CatalogItemId $previousCatalogId,
        public ?CatalogItemId $newCatalogId,
    ) {
    }
}

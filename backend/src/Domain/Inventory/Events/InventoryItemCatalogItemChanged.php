<?php

declare(strict_types=1);

namespace App\Domain\Inventory\Events;

use App\Domain\Catalog\Entity\CatalogItem;
use App\Domain\Inventory\Entity\InventoryItem;

final readonly class InventoryItemCatalogItemChanged
{
    public function __construct(
        public InventoryItem $inventoryItem,
        public ?CatalogItem $previousCatalogItem,
        public ?CatalogItem $newCatalogItem,
    ) {
    }
}

<?php

declare(strict_types=1);

namespace App\Domain\Inventory\Events;

use App\Domain\Inventory\Entity\InventoryItem;
use App\Domain\Shared\Id\CatalogItemId;

final readonly class InventoryItemCatalogItemChanged
{
    public function __construct(
        public InventoryItem $inventoryItem,
        public ?CatalogItemId $previousCatalogId,
        public ?CatalogItemId $newCatalogId,
    ) {
    }
}

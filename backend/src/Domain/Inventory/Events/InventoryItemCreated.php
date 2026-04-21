<?php

declare(strict_types=1);

namespace App\Domain\Inventory\Events;

use App\Domain\Inventory\Entity\InventoryItem;

final readonly class InventoryItemCreated
{
    public function __construct(
        public InventoryItem $inventoryItem,
    ) {
    }
}

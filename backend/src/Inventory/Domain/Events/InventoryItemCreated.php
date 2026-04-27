<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Events;

use App\Inventory\Domain\Entity\InventoryItem;

final readonly class InventoryItemCreated
{
    public function __construct(
        public InventoryItem $inventoryItem,
    ) {
    }
}

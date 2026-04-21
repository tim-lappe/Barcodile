<?php

declare(strict_types=1);

namespace App\Domain\Inventory\Events;

use App\Domain\Inventory\Entity\InventoryItem;

final readonly class InventoryItemQuantityChanged
{
    public function __construct(
        public InventoryItem $inventoryItem,
        public string $previousQuantity,
        public string $newQuantity,
    ) {
    }
}

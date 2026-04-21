<?php

declare(strict_types=1);

namespace App\Domain\Inventory\Events;

use App\Domain\Inventory\Entity\InventoryItem;
use App\Domain\Inventory\Entity\Location;

final readonly class InventoryItemLocationChanged
{
    public function __construct(
        public InventoryItem $inventoryItem,
        public ?Location $previousLocation,
        public ?Location $newLocation,
    ) {
    }
}

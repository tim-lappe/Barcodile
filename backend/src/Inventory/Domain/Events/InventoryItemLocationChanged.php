<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Events;

use App\Inventory\Domain\Entity\InventoryItem;
use App\Inventory\Domain\Entity\Location;

final readonly class InventoryItemLocationChanged
{
    public function __construct(
        public InventoryItem $inventoryItem,
        public ?Location $previousLocation,
        public ?Location $newLocation,
    ) {
    }
}

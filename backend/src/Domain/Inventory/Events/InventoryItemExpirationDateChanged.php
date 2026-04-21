<?php

declare(strict_types=1);

namespace App\Domain\Inventory\Events;

use App\Domain\Inventory\Entity\InventoryItem;
use DateTimeInterface;

final readonly class InventoryItemExpirationDateChanged
{
    public function __construct(
        public InventoryItem $inventoryItem,
        public ?DateTimeInterface $beforeExpiry,
        public ?DateTimeInterface $afterExpiry,
    ) {
    }
}

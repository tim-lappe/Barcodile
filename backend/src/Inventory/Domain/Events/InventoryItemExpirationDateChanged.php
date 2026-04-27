<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Events;

use App\Inventory\Domain\Entity\InventoryItem;
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

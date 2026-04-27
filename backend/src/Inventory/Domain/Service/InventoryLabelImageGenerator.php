<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Service;

use App\Inventory\Domain\ValueObject\InventoryItemCode;

interface InventoryLabelImageGenerator
{
    public function generate(InventoryItemCode $publicCode): string;
}

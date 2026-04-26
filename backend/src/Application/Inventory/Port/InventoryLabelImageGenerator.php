<?php

declare(strict_types=1);

namespace App\Application\Inventory\Port;

interface InventoryLabelImageGenerator
{
    public function generate(string $publicCode): string;
}

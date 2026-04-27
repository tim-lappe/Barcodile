<?php

declare(strict_types=1);

namespace App\Domain\Inventory\Facade;

interface InventoryLabelImageGenerator
{
    public function generate(string $publicCode): string;
}

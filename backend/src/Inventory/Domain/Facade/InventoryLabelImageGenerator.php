<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Facade;

interface InventoryLabelImageGenerator
{
    public function generate(string $publicCode): string;
}

<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Service;

interface InventoryLabelImageGenerator
{
    public function generate(string $publicCode): string;
}

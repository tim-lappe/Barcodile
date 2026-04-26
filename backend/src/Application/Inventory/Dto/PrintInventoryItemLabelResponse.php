<?php

declare(strict_types=1);

namespace App\Application\Inventory\Dto;

final readonly class PrintInventoryItemLabelResponse
{
    public function __construct(
        public string $status,
    ) {
    }
}

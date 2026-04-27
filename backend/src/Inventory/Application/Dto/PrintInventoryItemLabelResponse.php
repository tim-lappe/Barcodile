<?php

declare(strict_types=1);

namespace App\Inventory\Application\Dto;

final readonly class PrintInventoryItemLabelResponse
{
    public function __construct(
        public string $status,
    ) {
    }
}

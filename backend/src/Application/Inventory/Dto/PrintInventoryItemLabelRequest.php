<?php

declare(strict_types=1);

namespace App\Application\Inventory\Dto;

final readonly class PrintInventoryItemLabelRequest
{
    public function __construct(
        public string $printerDeviceId,
    ) {
    }
}

<?php

declare(strict_types=1);

namespace App\Inventory\Application\Dto;

final readonly class PrintInventoryItemLabelRequest
{
    public function __construct(
        public string $printerDeviceId,
    ) {
    }
}

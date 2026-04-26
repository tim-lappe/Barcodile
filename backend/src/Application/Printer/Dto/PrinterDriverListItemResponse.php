<?php

declare(strict_types=1);

namespace App\Application\Printer\Dto;

final readonly class PrinterDriverListItemResponse
{
    public function __construct(
        public string $code,
        public string $label,
    ) {
    }
}

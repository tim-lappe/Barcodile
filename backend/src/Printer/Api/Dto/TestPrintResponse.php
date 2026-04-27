<?php

declare(strict_types=1);

namespace App\Printer\Api\Dto;

final readonly class TestPrintResponse
{
    public function __construct(
        public string $status,
    ) {
    }
}

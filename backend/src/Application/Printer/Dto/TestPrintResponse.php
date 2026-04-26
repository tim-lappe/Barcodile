<?php

declare(strict_types=1);

namespace App\Application\Printer\Dto;

final readonly class TestPrintResponse
{
    public function __construct(
        public string $status,
    ) {
    }
}

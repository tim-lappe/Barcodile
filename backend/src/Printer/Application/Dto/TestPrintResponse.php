<?php

declare(strict_types=1);

namespace App\Printer\Application\Dto;

final readonly class TestPrintResponse
{
    public function __construct(
        public string $status,
    ) {
    }
}

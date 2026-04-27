<?php

declare(strict_types=1);

namespace App\Catalog\Api\Dto;

final readonly class WeightResponse
{
    public function __construct(
        public string $amount,
        public string $unit,
    ) {
    }
}

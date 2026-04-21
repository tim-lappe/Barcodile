<?php

declare(strict_types=1);

namespace App\Application\Catalog\Dto;

final readonly class VolumeResponse
{
    public function __construct(
        public string $amount,
        public string $unit,
    ) {
    }
}

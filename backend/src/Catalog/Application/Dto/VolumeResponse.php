<?php

declare(strict_types=1);

namespace App\Catalog\Application\Dto;

final readonly class VolumeResponse
{
    public function __construct(
        public string $amount,
        public string $unit,
    ) {
    }
}

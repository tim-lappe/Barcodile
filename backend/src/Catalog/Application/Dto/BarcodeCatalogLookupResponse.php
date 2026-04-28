<?php

declare(strict_types=1);

namespace App\Catalog\Application\Dto;

final readonly class BarcodeCatalogLookupResponse
{
    public function __construct(
        public string $providerId,
        public string $name,
        public ?VolumeResponse $volume,
        public ?WeightResponse $weight,
        public string $barcodeCode,
        public string $barcodeType,
        public ?float $alcoholPercent,
    ) {
    }
}

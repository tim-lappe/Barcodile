<?php

declare(strict_types=1);

namespace App\Catalog\Application\Dto;

final readonly class BarcodeCatalogProductHintResponse
{
    public function __construct(
        public string $providerId,
        public string $providerLabel,
        public string $name,
        public ?string $brand,
        public ?string $imageUrl,
        public ?string $category,
        public ?string $barcodeCode,
        public ?string $barcodeType,
    ) {
    }
}

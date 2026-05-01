<?php

declare(strict_types=1);

namespace App\Catalog\Domain\BarcodeLookup;

final readonly class BarcodeCatalogLookupDraft
{
    public function __construct(
        public string $providerId,
        public string $name,
        public ?string $volumeAmount,
        public ?string $volumeUnit,
        public ?string $weightAmount,
        public ?string $weightUnit,
        public ?float $alcoholPercent,
        public string $barcodeCode,
        public string $barcodeType,
        public ?string $picnicProductId = null,
        public ?string $productImageUrl = null,
    ) {
    }
}

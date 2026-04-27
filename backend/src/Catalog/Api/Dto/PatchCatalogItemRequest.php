<?php

declare(strict_types=1);

namespace App\Catalog\Api\Dto;

final readonly class PatchCatalogItemRequest
{
    public function __construct(
        public bool $nameSpecified,
        public ?string $name,
        public bool $volumeSpecified,
        public ?CatalogVolumeInput $volume,
        public bool $weightSpecified,
        public ?CatalogWeightInput $weight,
        public bool $barcodeSpecified,
        public ?CatalogBarcodeInput $barcode,
        public PatchCatalogItemRelationsPatch $relations,
    ) {
    }
}

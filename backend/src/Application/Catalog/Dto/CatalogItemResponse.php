<?php

declare(strict_types=1);

namespace App\Application\Catalog\Dto;

use Symfony\Component\Serializer\Attribute\SerializedName;

final readonly class CatalogItemResponse
{
    /**
     * @param list<BarcodeResponse>              $barcodes
     * @param list<CatalogItemAttributeResponse> $itemAttributes
     */
    public function __construct(
        #[SerializedName('id')]
        public string $resourceId,
        public string $name,
        public ?string $imageFileName,
        public ?VolumeResponse $volume,
        public ?WeightResponse $weight,
        public array $barcodes,
        #[SerializedName('catalogItemAttributes')]
        public array $itemAttributes,
        #[SerializedName('linkedPicnicProductId')]
        public ?string $picnicProductLink,
    ) {
    }
}

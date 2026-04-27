<?php

declare(strict_types=1);

namespace App\Catalog\Api\Dto;

use App\Catalog\Application\CatalogItemCreationSource;
use Symfony\Component\Serializer\Attribute\SerializedName;

final readonly class PostCatalogItemRequest
{
    /**
     * @param list<CatalogItemAttributeRowInput>|null $itemAttributes
     */
    public function __construct(
        public string $name,
        public ?CatalogVolumeInput $volume = null,
        public ?CatalogWeightInput $weight = null,
        public ?CatalogBarcodeInput $barcode = null,
        #[SerializedName('catalogItemAttributes')]
        public ?array $itemAttributes = null,
        #[SerializedName('linkedPicnicProductId')]
        public ?string $picnicProductLink = null,
        public CatalogItemCreationSource $creationSource = CatalogItemCreationSource::Manual,
    ) {
    }
}

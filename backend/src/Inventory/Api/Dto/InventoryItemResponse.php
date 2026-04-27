<?php

declare(strict_types=1);

namespace App\Inventory\Api\Dto;

use App\Catalog\Api\Dto\CatalogItemResponse;
use Symfony\Component\Serializer\Attribute\SerializedName;

final readonly class InventoryItemResponse
{
    public function __construct(
        #[SerializedName('id')]
        public string $resourceId,
        public string $publicCode,
        public CatalogItemResponse $catalogItem,
        public ?LocationResponse $location,
        public ?string $expirationDate,
        public string $createdAt,
    ) {
    }
}

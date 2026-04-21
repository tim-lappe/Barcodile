<?php

declare(strict_types=1);

namespace App\Application\Inventory\Dto;

use App\Application\Catalog\Dto\CatalogItemResponse;
use App\Application\Location\Dto\LocationResponse;
use Symfony\Component\Serializer\Attribute\SerializedName;

final readonly class InventoryItemResponse
{
    public function __construct(
        #[SerializedName('id')]
        public string $resourceId,
        public CatalogItemResponse $catalogItem,
        public ?LocationResponse $location,
        public string $quantity,
        public ?string $expirationDate,
        public string $createdAt,
    ) {
    }
}

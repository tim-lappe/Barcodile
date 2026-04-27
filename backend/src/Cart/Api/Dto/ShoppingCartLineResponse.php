<?php

declare(strict_types=1);

namespace App\Cart\Api\Dto;

use App\Catalog\Api\Dto\CatalogItemResponse;
use Symfony\Component\Serializer\Attribute\SerializedName;

final readonly class ShoppingCartLineResponse
{
    public function __construct(
        #[SerializedName('id')]
        public string $lineId,
        public CatalogItemResponse $catalogItem,
        public int $quantity,
        public string $createdAt,
    ) {
    }
}

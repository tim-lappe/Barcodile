<?php

declare(strict_types=1);

namespace App\Cart\Domain\Facade;

final readonly class ShoppingCartLineView
{
    public function __construct(
        public string $resourceId,
        public CartCatalogItemView $catalogItem,
        public int $quantity,
        public string $createdAt,
    ) {
    }
}

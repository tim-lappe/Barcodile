<?php

declare(strict_types=1);

namespace App\Cart\Domain\Facade;

use App\Catalog\Domain\Facade\CatalogItemView;

final readonly class CartCatalogItemView
{
    public function __construct(
        public string $resourceId,
        public string $name,
        public ?CatalogItemView $catalogItem,
    ) {
    }
}

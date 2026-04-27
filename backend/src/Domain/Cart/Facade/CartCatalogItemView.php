<?php

declare(strict_types=1);

namespace App\Domain\Cart\Facade;

use App\Domain\Catalog\Facade\CatalogItemView;

final readonly class CartCatalogItemView
{
    public function __construct(
        public string $resourceId,
        public string $name,
        public ?CatalogItemView $catalogItem,
    ) {
    }
}

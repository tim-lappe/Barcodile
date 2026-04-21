<?php

declare(strict_types=1);

namespace App\Domain\Picnic\Model;

final readonly class PicnicCatalogProductDetails
{
    public function __construct(
        public string $productId,
        public string $name,
        public string $brand,
        public string $unitQuantity,
    ) {
    }
}

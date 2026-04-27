<?php

declare(strict_types=1);

namespace App\Domain\Picnic\Facade;

final readonly class PicnicCatalogProductView
{
    public function __construct(
        public string $productId,
        public string $name,
        public ?string $imageId,
        public ?int $displayPrice,
        public ?string $unitQuantity,
        public ?string $brand = null,
    ) {
    }
}

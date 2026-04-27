<?php

declare(strict_types=1);

namespace App\Picnic\Domain\Facade;

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

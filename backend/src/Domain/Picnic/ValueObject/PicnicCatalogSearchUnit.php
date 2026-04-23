<?php

declare(strict_types=1);

namespace App\Domain\Picnic\ValueObject;

final readonly class PicnicCatalogSearchUnit
{
    public function __construct(
        public string $productId,
        public string $name,
        public ?string $imageId,
        public ?int $displayPrice,
        public ?string $unitQuantity,
    ) {
    }
}

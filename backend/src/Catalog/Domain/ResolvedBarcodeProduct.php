<?php

declare(strict_types=1);

namespace App\Catalog\Domain;

final readonly class ResolvedBarcodeProduct
{
    public function __construct(
        public string $name,
        public ?string $brand,
        public ?string $imageUrl,
        public ?string $category,
        public ?string $barcodeCode,
        public ?string $barcodeType,
    ) {
    }
}

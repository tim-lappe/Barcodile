<?php

declare(strict_types=1);

namespace App\Application\Catalog\Dto;

final readonly class CatalogBarcodeInput
{
    public function __construct(
        public string $code,
        public string $type = 'EAN',
    ) {
    }
}

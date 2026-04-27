<?php

declare(strict_types=1);

namespace App\Catalog\Api\Dto;

final readonly class CatalogBarcodeInput
{
    public function __construct(
        public string $code,
        public string $type = 'EAN',
    ) {
    }
}

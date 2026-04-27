<?php

declare(strict_types=1);

namespace App\Catalog\Application\Dto;

use App\SharedKernel\Domain\Barcode;

final readonly class CatalogBarcodeInput
{
    public function __construct(
        public string $code,
        public string $type = Barcode::DEFAULT_SYMBOLOGY,
    ) {
    }
}

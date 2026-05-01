<?php

declare(strict_types=1);

namespace App\Catalog\Domain\BarcodeLookup;

use App\SharedKernel\Domain\Barcode;

final readonly class BarcodeCatalogLookupDraft
{
    public function __construct(
        public string $providerId,
        public string $name,
        public ?string $volumeAmount,
        public ?string $volumeUnit,
        public ?string $weightAmount,
        public ?string $weightUnit,
        public ?float $alcoholPercent,
        public Barcode $barcode,
        public ?BarcodeCatalogLookupDraftExtras $extras = null,
    ) {
    }
}

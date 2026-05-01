<?php

declare(strict_types=1);

namespace App\Catalog\Domain\BarcodeLookup;

final readonly class BarcodeCatalogLookupDraftExtras
{
    public function __construct(
        public ?string $picnicProductId,
        public ?string $productImageUrl,
    ) {
    }
}

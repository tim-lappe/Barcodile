<?php

declare(strict_types=1);

namespace App\Catalog\Domain\Port;

use App\Catalog\Domain\BarcodeLookup\BarcodeCatalogLookupDraft;
use App\SharedKernel\Domain\Barcode;

interface BarcodeCatalogLookupProvider
{
    public function lookup(Barcode $barcode): BarcodeCatalogLookupDraft;
}

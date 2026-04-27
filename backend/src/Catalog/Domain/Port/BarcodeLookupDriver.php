<?php

declare(strict_types=1);

namespace App\Catalog\Domain\Port;

use App\Catalog\Domain\BarcodeLookupDriverResult;
use App\Catalog\Domain\BarcodeLookupProviderKind;

interface BarcodeLookupDriver
{
    public function supports(BarcodeLookupProviderKind $kind): bool;

    public function lookup(string $apiKey, string $barcode): BarcodeLookupDriverResult;
}

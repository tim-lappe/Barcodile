<?php

declare(strict_types=1);

namespace App\Catalog\Domain;

enum BarcodeLookupProviderKind: string
{
    case BarcodeLookupComV3 = 'barcode_lookup_com_v3';
}

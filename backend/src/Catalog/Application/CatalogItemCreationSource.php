<?php

declare(strict_types=1);

namespace App\Catalog\Application;

enum CatalogItemCreationSource: string
{
    case Manual = 'manual';
    case Picnic = 'picnic';
    case Barcode = 'barcode';
}

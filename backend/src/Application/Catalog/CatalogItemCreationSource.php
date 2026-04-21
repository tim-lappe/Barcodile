<?php

declare(strict_types=1);

namespace App\Application\Catalog;

enum CatalogItemCreationSource: string
{
    case Manual = 'manual';
    case Picnic = 'picnic';
    case Fddb = 'fddb';
}

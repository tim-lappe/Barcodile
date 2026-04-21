<?php

declare(strict_types=1);

namespace App\Domain\Picnic\Port;

use App\Domain\Picnic\Model\PicnicCatalogProductDetails;

interface PicnicCatalogProductLookupPort
{
    public function lookupByProductId(string $productId): PicnicCatalogProductDetails;
}

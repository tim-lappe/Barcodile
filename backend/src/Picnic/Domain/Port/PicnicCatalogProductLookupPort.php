<?php

declare(strict_types=1);

namespace App\Picnic\Domain\Port;

use App\Picnic\Domain\Model\PicnicCatalogProductDetails;

interface PicnicCatalogProductLookupPort
{
    public function lookupByProductId(string $productId): PicnicCatalogProductDetails;
}

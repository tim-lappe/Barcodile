<?php

declare(strict_types=1);

namespace App\Catalog\Domain\Port;

use App\Catalog\Domain\CatalogRemoteProductImageFetchResult;

interface CatalogRemoteProductImageFetchPort
{
    public function tryFetch(string $httpsUrl): ?CatalogRemoteProductImageFetchResult;
}

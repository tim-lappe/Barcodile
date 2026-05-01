<?php

declare(strict_types=1);

namespace App\Catalog\Domain;

final readonly class CatalogRemoteProductImageFetchResult
{
    public function __construct(
        public string $body,
        public CatalogImageContentType $contentType,
        public string $suggestedFileName,
    ) {
    }
}

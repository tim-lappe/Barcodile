<?php

declare(strict_types=1);

namespace App\Domain\Catalog;

final readonly class CatalogItemImageBlob
{
    public function __construct(
        public string $body,
        public ?string $contentType,
        public ?string $eTag,
    ) {
    }
}

<?php

declare(strict_types=1);

namespace App\Application\Catalog\Dto;

final readonly class CatalogItemImageGetResult
{
    public function __construct(
        public string $body,
        public string $contentType,
        public ?string $eTag,
    ) {
    }
}

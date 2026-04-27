<?php

declare(strict_types=1);

namespace App\Catalog\Application\Dto;

final readonly class CatalogItemImageGetResult
{
    public function __construct(
        public string $body,
        public string $contentType,
        public ?string $eTag,
    ) {
    }
}

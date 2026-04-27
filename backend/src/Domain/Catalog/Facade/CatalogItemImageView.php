<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Facade;

final readonly class CatalogItemImageView
{
    public function __construct(
        public string $body,
        public string $contentType,
        public ?string $eTag,
    ) {
    }
}

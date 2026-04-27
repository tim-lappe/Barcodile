<?php

declare(strict_types=1);

namespace App\Catalog\Domain\Facade;

final readonly class CatalogItemImageView
{
    public function __construct(
        public string $body,
        public string $contentType,
        public ?string $eTag,
    ) {
    }
}

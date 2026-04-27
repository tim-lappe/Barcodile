<?php

declare(strict_types=1);

namespace App\Catalog\Domain\Facade;

final readonly class CatalogItemAttributeView
{
    public function __construct(
        public string $resourceId,
        public string $attribute,
        public mixed $value,
    ) {
    }
}

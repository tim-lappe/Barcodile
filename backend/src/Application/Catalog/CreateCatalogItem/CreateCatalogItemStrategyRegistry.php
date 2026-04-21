<?php

declare(strict_types=1);

namespace App\Application\Catalog\CreateCatalogItem;

use App\Application\Catalog\CatalogItemCreationSource;

final readonly class CreateCatalogItemStrategyRegistry
{
    public function __construct(
        private ManualCreateCatalogItemStrategy $manual,
        private PicnicCreateCatalogItemStrategy $picnic,
        private FddbCreateCatalogItemStrategy $fddb,
    ) {
    }

    public function get(CatalogItemCreationSource $source): CreateCatalogItemStrategyInterface
    {
        return match ($source) {
            CatalogItemCreationSource::Manual => $this->manual,
            CatalogItemCreationSource::Picnic => $this->picnic,
            CatalogItemCreationSource::Fddb => $this->fddb,
        };
    }
}

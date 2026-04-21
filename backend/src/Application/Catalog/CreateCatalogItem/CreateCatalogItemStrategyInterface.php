<?php

declare(strict_types=1);

namespace App\Application\Catalog\CreateCatalogItem;

use App\Application\Catalog\Dto\CatalogItemResponse;
use App\Application\Catalog\Dto\PostCatalogItemRequest;

interface CreateCatalogItemStrategyInterface
{
    public function create(PostCatalogItemRequest $request): CatalogItemResponse;
}

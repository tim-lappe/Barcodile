<?php

declare(strict_types=1);

namespace App\Application\Catalog\CreateCatalogItem;

use App\Application\Catalog\Dto\CatalogItemResponse;
use App\Application\Catalog\Dto\PostCatalogItemRequest;

final readonly class FddbCreateCatalogItemStrategy implements CreateCatalogItemStrategyInterface
{
    public function __construct(
        private ManualCreateCatalogItemStrategy $manualStrategy,
    ) {
    }

    public function create(PostCatalogItemRequest $request): CatalogItemResponse
    {
        return $this->manualStrategy->create($request);
    }
}

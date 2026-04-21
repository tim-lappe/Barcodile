<?php

declare(strict_types=1);

namespace App\Application\Catalog\CreateCatalogItem;

use App\Application\Catalog\CatalogItemCreationPipeline;
use App\Application\Catalog\Dto\CatalogItemResponse;
use App\Application\Catalog\Dto\PostCatalogItemRequest;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final readonly class ManualCreateCatalogItemStrategy implements CreateCatalogItemStrategyInterface
{
    public function __construct(
        private CatalogItemCreationPipeline $creationPipeline,
    ) {
    }

    public function create(PostCatalogItemRequest $request): CatalogItemResponse
    {
        $trimmed = trim($request->name);
        if ('' === $trimmed) {
            throw new BadRequestHttpException('Field name must be a non-empty string.');
        }

        return $this->creationPipeline->persistNew($request, $trimmed);
    }
}

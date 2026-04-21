<?php

declare(strict_types=1);

namespace App\Application\Catalog\CreateCatalogItem;

use App\Application\Catalog\CatalogItemCreationPipeline;
use App\Application\Catalog\Dto\CatalogItemResponse;
use App\Application\Catalog\Dto\PostCatalogItemRequest;
use App\Domain\Picnic\Port\PicnicCatalogProductLookupPort;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Throwable;

final readonly class PicnicCreateCatalogItemStrategy implements CreateCatalogItemStrategyInterface
{
    public function __construct(
        private CatalogItemCreationPipeline $creationPipeline,
        private PicnicCatalogProductLookupPort $productLookupPort,
    ) {
    }

    public function create(PostCatalogItemRequest $request): CatalogItemResponse
    {
        $productId = $this->requirePicnicProductId($request);
        $name = $this->resolvedItemName($request, $productId);

        return $this->creationPipeline->persistNew($request, $name);
    }

    private function requirePicnicProductId(PostCatalogItemRequest $request): string
    {
        $productId = null !== $request->picnicProductLink ? trim($request->picnicProductLink) : '';
        if ('' === $productId) {
            throw new BadRequestHttpException('Picnic product id is required for this creation mode.');
        }

        return $productId;
    }

    private function resolvedItemName(PostCatalogItemRequest $request, string $pid): string
    {
        $name = trim($request->name);
        if ('' !== $name) {
            return $name;
        }
        try {
            $details = $this->productLookupPort->lookupByProductId($pid);
            $name = trim($details->name);
        } catch (Throwable) {
            throw new BadRequestHttpException('Could not load Picnic product details.');
        }
        if ('' === $name) {
            throw new BadRequestHttpException('Field name must be a non-empty string.');
        }

        return $name;
    }
}

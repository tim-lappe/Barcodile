<?php

declare(strict_types=1);

namespace App\Picnic\Application;

use App\Picnic\Api\Dto\PicnicCatalogProductSummaryResponse;
use App\Picnic\Api\Dto\PicnicCatalogSearchHitResponse;
use App\Picnic\Domain\Facade\PicnicFacade;

final readonly class PicnicCatalogOperations
{
    public function __construct(
        private PicnicFacade $picnic,
    ) {
    }

    /**
     * @return list<PicnicCatalogSearchHitResponse>
     */
    public function search(string $query): array
    {
        $hits = [];
        foreach ($this->picnic->searchCatalog($query) as $unit) {
            $hits[] = new PicnicCatalogSearchHitResponse(
                $unit->productId,
                $unit->name,
                $unit->imageId,
                $unit->displayPrice,
                $unit->unitQuantity,
            );
        }

        return $hits;
    }

    public function productSummary(string $productId): PicnicCatalogProductSummaryResponse
    {
        $summary = $this->picnic->productSummary($productId);

        return new PicnicCatalogProductSummaryResponse(
            $summary->productId,
            $summary->name,
            $summary->brand ?? '',
            $summary->unitQuantity ?? '',
        );
    }
}

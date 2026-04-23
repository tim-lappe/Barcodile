<?php

declare(strict_types=1);

namespace App\Application\Picnic;

use App\Application\Picnic\Dto\PicnicCatalogProductSummaryResponse;
use App\Application\Picnic\Dto\PicnicCatalogSearchHitResponse;
use App\Domain\Picnic\Port\PicnicCatalogProductLookupPort;
use App\Domain\Picnic\Port\PicnicCatalogSearchPort;

final readonly class PicnicCatalogOperations
{
    public function __construct(
        private PicnicCatalogProductLookupPort $catalogLookup,
        private PicnicCatalogSearchPort $catalogSearch,
    ) {
    }

    /**
     * @return list<PicnicCatalogSearchHitResponse>
     */
    public function search(string $query): array
    {
        $units = $this->catalogSearch->search($query);
        $hits = [];
        foreach ($units as $unit) {
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
        $summary = $this->catalogLookup->lookupByProductId($productId);

        return new PicnicCatalogProductSummaryResponse(
            $summary->productId,
            $summary->name,
            $summary->brand,
            $summary->unitQuantity,
        );
    }
}

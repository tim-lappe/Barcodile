<?php

declare(strict_types=1);

namespace App\Catalog\Application;

use App\Catalog\Api\Dto\BarcodeResponse;
use App\Catalog\Api\Dto\CatalogItemAttributeResponse;
use App\Catalog\Api\Dto\CatalogItemResponse;
use App\Catalog\Api\Dto\VolumeResponse;
use App\Catalog\Api\Dto\WeightResponse;

final readonly class CatalogItemResponseMapper
{
    public function fromMinimal(string $catalogItemId, string $name, ?string $picnicProductId): CatalogItemResponse
    {
        return new CatalogItemResponse($catalogItemId, $name, null, null, null, null, [], $picnicProductId);
    }

    public function fromView(CatalogItemView $item): CatalogItemResponse
    {
        return new CatalogItemResponse(
            $item->resourceId,
            $item->name,
            $item->imageFileName,
            $this->volume($item),
            $this->weight($item),
            $this->barcode($item),
            $this->attributes($item),
            $item->picnicProductId,
        );
    }

    private function volume(CatalogItemView $item): ?VolumeResponse
    {
        if (null === $item->volumeAmount || null === $item->volumeUnit) {
            return null;
        }

        return new VolumeResponse($item->volumeAmount, $item->volumeUnit);
    }

    private function weight(CatalogItemView $item): ?WeightResponse
    {
        if (null === $item->weightAmount || null === $item->weightUnit) {
            return null;
        }

        return new WeightResponse($item->weightAmount, $item->weightUnit);
    }

    private function barcode(CatalogItemView $item): ?BarcodeResponse
    {
        if (null === $item->barcodeCode || null === $item->barcodeType) {
            return null;
        }

        return new BarcodeResponse($item->barcodeCode, $item->barcodeType);
    }

    /**
     * @return list<CatalogItemAttributeResponse>
     */
    private function attributes(CatalogItemView $item): array
    {
        return array_map(
            static fn (CatalogItemAttributeView $attribute): CatalogItemAttributeResponse => new CatalogItemAttributeResponse(
                $attribute->resourceId,
                $attribute->attribute,
                $attribute->value,
            ),
            $item->attributes,
        );
    }
}

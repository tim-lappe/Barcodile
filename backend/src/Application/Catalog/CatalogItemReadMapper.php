<?php

declare(strict_types=1);

namespace App\Application\Catalog;

use App\Application\Catalog\Dto\BarcodeResponse;
use App\Application\Catalog\Dto\CatalogItemAttributeResponse;
use App\Application\Catalog\Dto\CatalogItemResponse;
use App\Application\Catalog\Dto\VolumeResponse;
use App\Application\Catalog\Dto\WeightResponse;
use App\Domain\Catalog\Entity\CatalogItem;

final readonly class CatalogItemReadMapper
{
    public function map(CatalogItem $item, ?string $picnicProductId): CatalogItemResponse
    {
        return new CatalogItemResponse(
            (string) $item->getId(),
            $item->getName(),
            $item->getImageFileName(),
            $this->volumeResponse($item),
            $this->weightResponse($item),
            $this->barcodeResponse($item),
            $this->attributeResponses($item),
            $picnicProductId,
        );
    }

    private function volumeResponse(CatalogItem $item): ?VolumeResponse
    {
        $vol = $item->getVolume();

        return null === $vol ? null : new VolumeResponse($vol->getAmount(), $vol->getUnit()->value);
    }

    private function weightResponse(CatalogItem $item): ?WeightResponse
    {
        $weight = $item->getWeight();

        return null === $weight ? null : new WeightResponse($weight->getAmount(), $weight->getUnit()->value);
    }

    private function barcodeResponse(CatalogItem $item): ?BarcodeResponse
    {
        $value = $item->getBarcode();
        if (null === $value) {
            return null;
        }

        return new BarcodeResponse($value->getCode(), $value->getType());
    }

    /**
     * @return list<CatalogItemAttributeResponse>
     */
    private function attributeResponses(CatalogItem $item): array
    {
        $attrs = [];
        foreach ($item->getCatalogItemAttributes() as $attributeEntity) {
            $key = $attributeEntity->getAttribute();
            $attrs[] = new CatalogItemAttributeResponse(
                (string) $attributeEntity->getId(),
                null !== $key ? $key->value : '',
                $attributeEntity->getValue(),
            );
        }

        return $attrs;
    }
}

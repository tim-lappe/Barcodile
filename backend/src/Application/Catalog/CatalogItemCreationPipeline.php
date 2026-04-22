<?php

declare(strict_types=1);

namespace App\Application\Catalog;

use App\Application\Catalog\Dto\CatalogBarcodeInput;
use App\Application\Catalog\Dto\CatalogItemResponse;
use App\Application\Catalog\Dto\CatalogVolumeInput;
use App\Application\Catalog\Dto\CatalogWeightInput;
use App\Application\Catalog\Dto\PostCatalogItemRequest;
use App\Domain\Catalog\Entity\CatalogItem;
use App\Domain\Catalog\Repository\CatalogItemRepository;
use App\Domain\Picnic\Entity\PicnicCatalogItemProductLink;
use App\Domain\Picnic\Repository\PicnicCatalogItemProductLinkRepository;
use App\Domain\Shared\Barcode as BarcodeValue;
use App\Domain\Shared\Volume;
use App\Domain\Shared\VolumeUnit;
use App\Domain\Shared\Weight;
use App\Domain\Shared\WeightUnit;
use Doctrine\ORM\EntityManagerInterface;

final readonly class CatalogItemCreationPipeline
{
    public function __construct(
        private CatalogItemRepository $catalogItemRepo,
        private PicnicCatalogItemProductLinkRepository $picnicLinkRepo,
        private EntityManagerInterface $entityManager,
        private CatalogItemReadMapper $readMapper,
        private CatalogItemAttributeRowsApplier $attributeRowsApplier,
    ) {
    }

    public function persistNew(PostCatalogItemRequest $request, string $trimmedName): CatalogItemResponse
    {
        $item = new CatalogItem();
        $item->changeName($trimmedName);
        $item->changeVolume($this->volumeInputToDomain($request->volume));
        $item->changeWeight($this->weightInputToDomain($request->weight));
        $this->catalogItemRepo->save($item);
        $this->applyBarcodeFromInput($item, $request->barcode);
        $this->attributeRowsApplier->apply($item, $request->itemAttributes);
        $this->applyPicnicLinkForCreate($item, $request->picnicProductLink);
        $this->entityManager->flush();
        $link = $this->picnicLinkRepo->findOneByCatalogItemId($item->getId());

        return $this->readMapper->map($item, $link?->getProductId());
    }

    private function volumeInputToDomain(?CatalogVolumeInput $input): ?Volume
    {
        if (null === $input) {
            return null;
        }

        return new Volume($input->amount, VolumeUnit::from($input->unit));
    }

    private function weightInputToDomain(?CatalogWeightInput $input): ?Weight
    {
        if (null === $input) {
            return null;
        }

        return new Weight($input->amount, WeightUnit::from($input->unit));
    }

    private function applyBarcodeFromInput(CatalogItem $item, ?CatalogBarcodeInput $barcode): void
    {
        if (null === $barcode) {
            $item->changeBarcode(null);

            return;
        }
        $code = trim($barcode->code);
        if ('' === $code) {
            $item->changeBarcode(null);

            return;
        }
        $item->changeBarcode(new BarcodeValue($code, $barcode->type));
    }

    private function applyPicnicLinkForCreate(CatalogItem $item, ?string $picnicProductId): void
    {
        if (null === $picnicProductId) {
            return;
        }
        $productId = trim($picnicProductId);
        if ('' === $productId) {
            return;
        }
        $this->entityManager->persist(new PicnicCatalogItemProductLink($item, $productId));
    }
}

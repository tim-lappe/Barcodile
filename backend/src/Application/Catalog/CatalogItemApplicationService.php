<?php

declare(strict_types=1);

namespace App\Application\Catalog;

use App\Application\Catalog\CreateCatalogItem\CreateCatalogItemStrategyRegistry;
use App\Application\Catalog\Dto\CatalogBarcodeInput;
use App\Application\Catalog\Dto\CatalogItemImageGetResult;
use App\Application\Catalog\Dto\CatalogItemResponse;
use App\Application\Catalog\Dto\CatalogVolumeInput;
use App\Application\Catalog\Dto\CatalogWeightInput;
use App\Application\Catalog\Dto\PatchCatalogItemRequest;
use App\Application\Catalog\Dto\PicnicCatalogProductHintResponse;
use App\Application\Catalog\Dto\PostCatalogItemRequest;
use App\Domain\Catalog\CatalogImageContentType;
use App\Domain\Catalog\Entity\CatalogItem;
use App\Domain\Catalog\Entity\CatalogItemImage;
use App\Domain\Catalog\Repository\CatalogItemRepository;
use App\Domain\Picnic\Port\PicnicCatalogProductLookupPort;
use App\Domain\Picnic\Repository\PicnicCatalogItemProductLinkRepository;
use App\Domain\Shared\Barcode as BarcodeValue;
use App\Domain\Shared\Id\CatalogItemId;
use App\Domain\Shared\Volume;
use App\Domain\Shared\VolumeUnit;
use App\Domain\Shared\Weight;
use App\Domain\Shared\WeightUnit;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @SuppressWarnings("PHPMD.ExcessiveClassComplexity")
 */
final readonly class CatalogItemApplicationService
{
    public function __construct(
        private CatalogItemRepository $catalogItemRepo,
        private PicnicCatalogItemProductLinkRepository $picnicLinkRepo,
        private CreateCatalogItemStrategyRegistry $itemStrategyReg,
        private PicnicCatalogProductLookupPort $picnicProductLookup,
        private CatalogItemReadMapper $readMapper,
        private CatalogItemAttributeRowsApplier $attributeRowsApplier,
        private CatalogItemPicnicLinkPatcher $picnicLinkPatcher,
    ) {
    }

    public function picnicProductHint(string $productId): PicnicCatalogProductHintResponse
    {
        $hint = $this->picnicProductLookup->lookupByProductId($productId);

        return new PicnicCatalogProductHintResponse(
            $hint->productId,
            $hint->name,
            $hint->brand,
            $hint->unitQuantity,
        );
    }

    /**
     * @return list<CatalogItemResponse>
     */
    public function listCatalogItems(int $page, int $itemsPerPage, string $orderNameDir, ?string $nameContains): array
    {
        $page = max(1, $page);
        $itemsPerPage = max(1, min(500, $itemsPerPage));
        $offset = ($page - 1) * $itemsPerPage;
        $rows = $this->catalogItemRepo->findPagedByNameOrder($offset, $itemsPerPage, $orderNameDir, $nameContains);
        $ids = array_map(static fn (CatalogItem $catalogItem) => $catalogItem->getId(), $rows);
        $picnic = [] === $ids ? [] : $this->picnicLinkRepo->mapProductIdByCatalogItemId($ids);
        $out = [];
        foreach ($rows as $row) {
            $pid = $picnic[(string) $row->getId()] ?? null;
            $out[] = $this->readMapper->map($row, $pid);
        }

        return $out;
    }

    public function getCatalogItem(CatalogItemId $catalogItemId): CatalogItemResponse
    {
        $item = $this->catalogItemRepo->find($catalogItemId);
        if (!$item instanceof CatalogItem) {
            throw new NotFoundHttpException('Catalog item not found.');
        }
        $link = $this->picnicLinkRepo->findOneByCatalogItemId($catalogItemId);

        return $this->readMapper->map($item, $link?->getProductId());
    }

    public function createCatalogItem(PostCatalogItemRequest $request): CatalogItemResponse
    {
        return $this->itemStrategyReg->get($request->creationSource)->create($request);
    }

    public function updateCatalogItem(CatalogItemId $catalogItemId, PatchCatalogItemRequest $request): void
    {
        $item = $this->mustFind($catalogItemId);
        $this->applyNameFromPatch($item, $request);
        $this->applyVolumeWeightFromPatch($item, $request);
        $this->applyBarcodeFromPatchInput($item, $request);
        $this->applyAttributesFromPatchInput($item, $request);
        $this->picnicLinkPatcher->applyFromPatch($item, $request);
        $this->catalogItemRepo->save($item);
    }

    public function deleteCatalogItem(CatalogItemId $catalogItemId): void
    {
        $item = $this->mustFind($catalogItemId);
        $this->catalogItemRepo->remove($item);
    }

    public function uploadCatalogItemImage(CatalogItemId $catalogItemId, UploadedFile $file): CatalogItemResponse
    {
        $item = $this->mustFind($catalogItemId);
        $binary = file_get_contents($file->getPathname());
        if (false === $binary) {
            throw new InvalidArgumentException('Uploaded file could not be read.');
        }
        $safe = $this->sanitizeCatalogImageFileName($file->getClientOriginalName());
        $mime = $file->getMimeType();
        $contentType = CatalogImageContentType::tryFromMimeType((string) $mime) ?? CatalogImageContentType::Jpeg;
        $item->assignImage($safe, $binary, $contentType);
        $this->catalogItemRepo->save($item);
        $link = $this->picnicLinkRepo->findOneByCatalogItemId($catalogItemId);

        return $this->readMapper->map($item, $link?->getProductId());
    }

    public function deleteCatalogItemImage(CatalogItemId $catalogItemId): CatalogItemResponse
    {
        $item = $this->mustFind($catalogItemId);
        if (null !== $item->getImageFileName() && '' !== $item->getImageFileName()) {
            $item->clearImage();
            $this->catalogItemRepo->save($item);
        }
        $link = $this->picnicLinkRepo->findOneByCatalogItemId($catalogItemId);

        return $this->readMapper->map($item, $link?->getProductId());
    }

    public function getCatalogItemImage(CatalogItemId $catalogItemId): CatalogItemImageGetResult
    {
        $item = $this->catalogItemRepo->findWithCatalogItemImage($catalogItemId);
        if (!$item instanceof CatalogItem) {
            throw new NotFoundHttpException('Catalog item not found.');
        }

        return $this->catalogItemImageGetResultFromLoadedItem($item);
    }

    private function mustFind(CatalogItemId $catalogItemId): CatalogItem
    {
        $item = $this->catalogItemRepo->find($catalogItemId);
        if (!$item instanceof CatalogItem) {
            throw new NotFoundHttpException('Catalog item not found.');
        }

        return $item;
    }

    private function catalogItemImageGetResultFromLoadedItem(CatalogItem $item): CatalogItemImageGetResult
    {
        return $this->catalogItemImageToGetResult($this->requireStoredCatalogItemImage($item));
    }

    private function requireStoredCatalogItemImage(CatalogItem $item): CatalogItemImage
    {
        $fileName = $item->getImageFileName();
        if (null === $fileName || '' === $fileName) {
            throw new NotFoundHttpException('Catalog item has no image.');
        }
        $image = $item->getCatalogItemImage();
        if (null === $image) {
            throw new NotFoundHttpException('Image not found in storage.');
        }

        return $image;
    }

    private function catalogItemImageToGetResult(CatalogItemImage $image): CatalogItemImageGetResult
    {
        $body = $image->getBody();
        $mime = $image->getContentType();

        return new CatalogItemImageGetResult(
            $body,
            ('' === $mime) ? 'application/octet-stream' : $mime,
            md5($body),
        );
    }

    private function sanitizeCatalogImageFileName(string $fileName): string
    {
        $trimmed = trim($fileName);
        $base = basename(str_replace('\\', '/', $trimmed));

        return '' === $base ? 'image' : $base;
    }

    private function applyNameFromPatch(CatalogItem $item, PatchCatalogItemRequest $request): void
    {
        if ($request->nameSpecified && null !== $request->name) {
            $item->changeName($request->name);
        }
    }

    private function applyVolumeWeightFromPatch(CatalogItem $item, PatchCatalogItemRequest $request): void
    {
        $this->applyVolumeIfSpecified($item, $request);
        $this->applyWeightIfSpecified($item, $request);
    }

    private function applyVolumeIfSpecified(CatalogItem $item, PatchCatalogItemRequest $request): void
    {
        if (!$request->volumeSpecified) {
            return;
        }
        $item->changeVolume($this->volumeInputToDomain($request->volume));
    }

    private function applyWeightIfSpecified(CatalogItem $item, PatchCatalogItemRequest $request): void
    {
        if (!$request->weightSpecified) {
            return;
        }
        $item->changeWeight($this->weightInputToDomain($request->weight));
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

    private function applyBarcodeFromPatchInput(CatalogItem $item, PatchCatalogItemRequest $request): void
    {
        if (!$request->barcodeSpecified) {
            return;
        }
        $this->applyBarcodeFromInput($item, $request->barcode);
    }

    private function applyAttributesFromPatchInput(CatalogItem $item, PatchCatalogItemRequest $request): void
    {
        if (!$request->relations->attrsSpecified) {
            return;
        }
        $this->attributeRowsApplier->apply($item, $request->relations->attrs);
    }

    public function toCatalogItemResponse(CatalogItem $item, ?string $picnicProductId): CatalogItemResponse
    {
        return $this->readMapper->map($item, $picnicProductId);
    }

    public function minimalCatalogItemResponse(string $catalogItemId, string $name, ?string $picnicProductId): CatalogItemResponse
    {
        return new CatalogItemResponse($catalogItemId, $name, null, null, null, null, [], $picnicProductId);
    }
}

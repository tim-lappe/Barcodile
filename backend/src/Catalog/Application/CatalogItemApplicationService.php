<?php

declare(strict_types=1);

namespace App\Catalog\Application;

use App\Catalog\Api\Dto\BarcodeResponse;
use App\Catalog\Api\Dto\CatalogItemAttributeResponse;
use App\Catalog\Api\Dto\CatalogItemAttributeRowInput;
use App\Catalog\Api\Dto\CatalogItemImageGetResult;
use App\Catalog\Api\Dto\CatalogItemResponse;
use App\Catalog\Api\Dto\PatchCatalogItemRequest;
use App\Catalog\Api\Dto\PicnicCatalogProductHintResponse;
use App\Catalog\Api\Dto\PostCatalogItemRequest;
use App\Catalog\Api\Dto\VolumeResponse;
use App\Catalog\Api\Dto\WeightResponse;
use App\Catalog\Domain\Facade\CatalogFacade;
use App\Catalog\Domain\Facade\CatalogItemAttributeView;
use App\Catalog\Domain\Facade\CatalogItemView;
use App\Picnic\Domain\Facade\PicnicFacade;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * @SuppressWarnings("PHPMD.CyclomaticComplexity")
 */
final readonly class CatalogItemApplicationService
{
    public function __construct(
        private CatalogFacade $catalog,
        private PicnicFacade $picnic,
    ) {
    }

    public function picnicProductHint(string $productId): PicnicCatalogProductHintResponse
    {
        $hint = $this->picnic->productSummary($productId);

        return new PicnicCatalogProductHintResponse(
            $hint->productId,
            $hint->name,
            $hint->brand ?? '',
            $hint->unitQuantity ?? '',
        );
    }

    /**
     * @return list<CatalogItemResponse>
     */
    public function listCatalogItems(int $page, int $itemsPerPage, string $orderNameDir, ?string $nameContains): array
    {
        return array_map(
            fn (CatalogItemView $item): CatalogItemResponse => $this->map($item),
            $this->catalog->listCatalogItems($page, $itemsPerPage, $orderNameDir, $nameContains),
        );
    }

    public function getCatalogItem(string $catalogItemId): CatalogItemResponse
    {
        return $this->map($this->catalog->getCatalogItem($catalogItemId));
    }

    public function createCatalogItem(PostCatalogItemRequest $request): CatalogItemResponse
    {
        return $this->map($this->catalog->createCatalogItem(
            $request->name,
            $request->volume?->amount,
            $request->volume?->unit,
            $request->weight?->amount,
            $request->weight?->unit,
            $request->barcode?->code,
            $request->barcode?->type,
            $this->attributeRows($request->itemAttributes),
            $request->picnicProductLink,
            $request->creationSource->value,
        ));
    }

    public function updateCatalogItem(string $catalogItemId, PatchCatalogItemRequest $request): void
    {
        $this->catalog->updateCatalogItem(
            $catalogItemId,
            $request->nameSpecified,
            $request->name,
            $request->volumeSpecified,
            $request->volume?->amount,
            $request->volume?->unit,
            $request->weightSpecified,
            $request->weight?->amount,
            $request->weight?->unit,
            $request->barcodeSpecified,
            $request->barcode?->code,
            $request->barcode?->type,
            $request->relations->attrsSpecified,
            $this->attributeRows($request->relations->attrs),
            $request->relations->picnicLinkSpecified,
            $request->relations->picnicProductId,
        );
    }

    public function deleteCatalogItem(string $catalogItemId): void
    {
        $this->catalog->deleteCatalogItem($catalogItemId);
    }

    public function uploadCatalogItemImage(string $catalogItemId, UploadedFile $file): CatalogItemResponse
    {
        $binary = file_get_contents($file->getPathname());
        if (false === $binary) {
            throw new InvalidArgumentException('Uploaded file could not be read.');
        }

        return $this->map($this->catalog->uploadCatalogItemImage(
            $catalogItemId,
            $file->getClientOriginalName(),
            (string) $file->getMimeType(),
            $binary,
        ));
    }

    public function deleteCatalogItemImage(string $catalogItemId): CatalogItemResponse
    {
        return $this->map($this->catalog->deleteCatalogItemImage($catalogItemId));
    }

    public function getCatalogItemImage(string $catalogItemId): CatalogItemImageGetResult
    {
        $image = $this->catalog->getCatalogItemImage($catalogItemId);

        return new CatalogItemImageGetResult($image->body, $image->contentType, $image->eTag);
    }

    public function minimalCatalogItemResponse(string $catalogItemId, string $name, ?string $picnicProductId): CatalogItemResponse
    {
        return new CatalogItemResponse($catalogItemId, $name, null, null, null, null, [], $picnicProductId);
    }

    public function catalogItemResponse(CatalogItemView $item): CatalogItemResponse
    {
        return $this->map($item);
    }

    /**
     * @param list<CatalogItemAttributeRowInput>|null $rows
     *
     * @return list<array{rowId: string|null, attribute: string, value: mixed}>|null
     */
    private function attributeRows(?array $rows): ?array
    {
        if (null === $rows) {
            return null;
        }
        $out = [];
        foreach ($rows as $row) {
            $out[] = [
                'rowId' => $row->rowId,
                'attribute' => $row->attribute,
                'value' => $row->value,
            ];
        }

        return $out;
    }

    private function map(CatalogItemView $item): CatalogItemResponse
    {
        return new CatalogItemResponse(
            $item->resourceId,
            $item->name,
            $item->imageFileName,
            null === $item->volumeAmount || null === $item->volumeUnit ? null : new VolumeResponse($item->volumeAmount, $item->volumeUnit),
            null === $item->weightAmount || null === $item->weightUnit ? null : new WeightResponse($item->weightAmount, $item->weightUnit),
            null === $item->barcodeCode || null === $item->barcodeType ? null : new BarcodeResponse($item->barcodeCode, $item->barcodeType),
            array_map(
                static fn (CatalogItemAttributeView $attribute): CatalogItemAttributeResponse => new CatalogItemAttributeResponse(
                    $attribute->resourceId,
                    $attribute->attribute,
                    $attribute->value,
                ),
                $item->attributes,
            ),
            $item->picnicProductId,
        );
    }
}

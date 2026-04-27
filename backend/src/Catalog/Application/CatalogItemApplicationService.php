<?php

declare(strict_types=1);

namespace App\Catalog\Application;

use App\Catalog\Application\Dto\CatalogItemAttributeRowInput;
use App\Catalog\Application\Dto\CatalogItemImageGetResult;
use App\Catalog\Application\Dto\CatalogItemResponse;
use App\Catalog\Application\Dto\PatchCatalogItemRequest;
use App\Catalog\Application\Dto\PicnicCatalogProductHintResponse;
use App\Catalog\Application\Dto\PostCatalogItemRequest;
use App\Catalog\Domain\CatalogImageContentType;
use App\Catalog\Domain\Entity\CatalogItem;
use App\Catalog\Domain\Entity\CatalogItemAttribute;
use App\Catalog\Domain\Image;
use App\Catalog\Domain\Repository\CatalogItemRepository;
use App\Picnic\Application\PicnicIntegrationApplicationService;
use App\SharedKernel\Domain\Barcode as BarcodeValue;
use App\SharedKernel\Domain\CatalogItemAttributeKey;
use App\SharedKernel\Domain\Id\CatalogItemId;
use App\SharedKernel\Domain\Volume;
use App\SharedKernel\Domain\VolumeUnit;
use App\SharedKernel\Domain\Weight;
use App\SharedKernel\Domain\WeightUnit;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @SuppressWarnings("PHPMD.CyclomaticComplexity")
 * @SuppressWarnings("PHPMD.ExcessiveClassComplexity")
 * @SuppressWarnings("PHPMD.ExcessiveClassLength")
 */
final readonly class CatalogItemApplicationService
{
    public function __construct(
        private CatalogItemRepository $catalogItemRepo,
        private PicnicIntegrationApplicationService $picnic,
        private EntityManagerInterface $entityManager,
        private CatalogItemResponseMapper $responseMapper,
    ) {
    }

    public function picnicProductHint(string $productId): PicnicCatalogProductHintResponse
    {
        $hint = $this->picnic->catalogProductSummary($productId);

        return new PicnicCatalogProductHintResponse(
            $hint->resourceId,
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
        $picnic = $this->picnic->mapProductIdsByCatalogItemIds(array_map(static fn (CatalogItem $item): string => (string) $item->getId(), $rows));

        return array_map(
            fn (CatalogItem $item): CatalogItemResponse => $this->responseMapper->fromView($this->map($item, $picnic[(string) $item->getId()] ?? null)),
            $rows,
        );
    }

    public function getCatalogItem(string $catalogItemId): CatalogItemResponse
    {
        return $this->responseMapper->fromView($this->catalogItemView($catalogItemId));
    }

    public function ensureCatalogItemExists(string $catalogItemId): void
    {
        $this->mustFind(CatalogItemId::fromString($catalogItemId));
    }

    public function createCatalogItem(PostCatalogItemRequest $request): CatalogItemResponse
    {
        return $this->createCatalogItemFromValues(
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
        );
    }

    /**
     * @param list<array{rowId: string|null, attribute: string, value: mixed}>|null $attributes
     *
     * @SuppressWarnings("PHPMD.ExcessiveParameterList")
     */
    public function createCatalogItemFromValues(
        string $name,
        ?string $volumeAmount,
        ?string $volumeUnit,
        ?string $weightAmount,
        ?string $weightUnit,
        ?string $barcodeCode,
        ?string $barcodeType,
        ?array $attributes,
        ?string $picnicProductId,
        string $creationSource,
    ): CatalogItemResponse {
        $trimmedName = $this->resolveCreateName($name, $picnicProductId, $creationSource);
        $item = new CatalogItem();
        $item->changeName($trimmedName);
        $item->changeVolume($this->volumeInputToDomain($volumeAmount, $volumeUnit));
        $item->changeWeight($this->weightInputToDomain($weightAmount, $weightUnit));
        $this->catalogItemRepo->save($item);
        $this->applyBarcodeFromInput($item, $barcodeCode, $barcodeType);
        $this->applyAttributeRows($item, $attributes);
        $this->picnic->syncProductLinkForCatalogItem((string) $item->getId(), $picnicProductId);
        $this->entityManager->flush();

        return $this->responseMapper->fromView($this->map($item, $this->picnic->productIdForCatalogItem((string) $item->getId())));
    }

    /**
     * @SuppressWarnings("PHPMD.NPathComplexity")
     */
    public function updateCatalogItem(string $catalogItemId, PatchCatalogItemRequest $request): void
    {
        $item = $this->mustFind(CatalogItemId::fromString($catalogItemId));
        if ($request->nameSpecified && null !== $request->name) {
            $item->changeName($request->name);
        }
        if ($request->volumeSpecified) {
            $item->changeVolume($this->volumeInputToDomain($request->volume?->amount, $request->volume?->unit));
        }
        if ($request->weightSpecified) {
            $item->changeWeight($this->weightInputToDomain($request->weight?->amount, $request->weight?->unit));
        }
        if ($request->barcodeSpecified) {
            $this->applyBarcodeFromInput($item, $request->barcode?->code, $request->barcode?->type);
        }
        if ($request->relations->attrsSpecified) {
            $this->applyAttributeRows($item, $this->attributeRows($request->relations->attrs));
        }
        if ($request->relations->picnicLinkSpecified) {
            $this->picnic->syncProductLinkForCatalogItem((string) $item->getId(), $request->relations->picnicProductId);
        }
        $this->catalogItemRepo->save($item);
    }

    public function deleteCatalogItem(string $catalogItemId): void
    {
        $this->catalogItemRepo->remove($this->mustFind(CatalogItemId::fromString($catalogItemId)));
    }

    public function uploadCatalogItemImage(string $catalogItemId, UploadedFile $file): CatalogItemResponse
    {
        $binary = file_get_contents($file->getPathname());
        if (false === $binary) {
            throw new InvalidArgumentException('Uploaded file could not be read.');
        }

        $item = $this->mustFind(CatalogItemId::fromString($catalogItemId));
        $image = Image::fromCatalogContentType(
            $this->sanitizeCatalogImageFileName($file->getClientOriginalName()),
            $binary,
            CatalogImageContentType::tryFromMimeType((string) $file->getMimeType()) ?? CatalogImageContentType::Jpeg,
        );
        $item->assignImage($image);
        $this->catalogItemRepo->save($item);

        return $this->responseMapper->fromView($this->map($item, $this->picnic->productIdForCatalogItem($catalogItemId)));
    }

    public function deleteCatalogItemImage(string $catalogItemId): CatalogItemResponse
    {
        $item = $this->mustFind(CatalogItemId::fromString($catalogItemId));
        if (null !== $item->getImageFileName() && '' !== $item->getImageFileName()) {
            $item->clearImage();
            $this->catalogItemRepo->save($item);
        }

        return $this->responseMapper->fromView($this->map($item, $this->picnic->productIdForCatalogItem($catalogItemId)));
    }

    public function getCatalogItemImage(string $catalogItemId): CatalogItemImageGetResult
    {
        $item = $this->catalogItemRepo->findWithCatalogItemImage(CatalogItemId::fromString($catalogItemId));
        if (!$item instanceof CatalogItem) {
            throw new NotFoundHttpException('Catalog item not found.');
        }
        $image = $this->requireStoredCatalogItemImage($item);

        return new CatalogItemImageGetResult($image->getBody(), $image->getMimeType(), $image->getETag());
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

    private function catalogItemView(string $catalogItemId): CatalogItemView
    {
        return $this->map(
            $this->mustFind(CatalogItemId::fromString($catalogItemId)),
            $this->picnic->productIdForCatalogItem($catalogItemId),
        );
    }

    private function mustFind(CatalogItemId $catalogItemId): CatalogItem
    {
        $item = $this->catalogItemRepo->find($catalogItemId);
        if (!$item instanceof CatalogItem) {
            throw new NotFoundHttpException('Catalog item not found.');
        }

        return $item;
    }

    /**
     * @SuppressWarnings("PHPMD.CyclomaticComplexity")
     */
    private function resolveCreateName(string $name, ?string $picnicProductId, string $creationSource): string
    {
        $trimmed = trim($name);
        if ('picnic' !== $creationSource) {
            if ('' === $trimmed) {
                throw new BadRequestHttpException('Field name must be a non-empty string.');
            }

            return $trimmed;
        }
        $productId = null === $picnicProductId ? '' : trim($picnicProductId);
        if ('' === $productId) {
            throw new BadRequestHttpException('Picnic product id is required for this creation mode.');
        }
        if ('' !== $trimmed) {
            return $trimmed;
        }
        $summary = $this->picnic->catalogProductSummary($productId);
        $resolved = trim($summary->name);
        if ('' === $resolved) {
            throw new BadRequestHttpException('Field name must be a non-empty string.');
        }

        return $resolved;
    }

    private function volumeInputToDomain(?string $amount, ?string $unit): ?Volume
    {
        if (null === $amount || null === $unit) {
            return null;
        }

        return new Volume($amount, VolumeUnit::from($unit));
    }

    private function weightInputToDomain(?string $amount, ?string $unit): ?Weight
    {
        if (null === $amount || null === $unit) {
            return null;
        }

        return new Weight($amount, WeightUnit::from($unit));
    }

    private function applyBarcodeFromInput(CatalogItem $item, ?string $code, ?string $type): void
    {
        if (null === $code || null === $type) {
            $item->changeBarcode(null);

            return;
        }
        $code = trim($code);
        if ('' === $code) {
            $item->changeBarcode(null);

            return;
        }
        $item->changeBarcode(new BarcodeValue($code, $type));
    }

    /**
     * @param list<array{rowId: string|null, attribute: string, value: mixed}>|null $rows
     *
     * @SuppressWarnings("PHPMD.CyclomaticComplexity")
     */
    private function applyAttributeRows(CatalogItem $item, ?array $rows): void
    {
        $rows ??= [];
        $byId = [];
        foreach ($item->getCatalogItemAttributes() as $existing) {
            $byId[(string) $existing->getId()] = $existing;
        }
        $seen = [];
        foreach ($rows as $row) {
            $rowId = $row['rowId'];
            $attribute = null === $rowId ? null : ($byId[$rowId] ?? null);
            if (!$attribute instanceof CatalogItemAttribute) {
                $attribute = new CatalogItemAttribute();
                $item->addCatalogItemAttribute($attribute);
            }
            $attribute->changeAttribute(CatalogItemAttributeKey::from($row['attribute']));
            $attribute->changeValue($row['value']);
            $seen[(string) $attribute->getId()] = true;
        }
        foreach ($item->getCatalogItemAttributes()->toArray() as $existing) {
            if (!isset($seen[(string) $existing->getId()])) {
                $item->removeCatalogItemAttribute($existing);
            }
        }
    }

    private function requireStoredCatalogItemImage(CatalogItem $item): Image
    {
        $image = $item->getImage();
        if (null === $image) {
            throw new NotFoundHttpException('Catalog item has no image.');
        }

        return $image;
    }

    private function sanitizeCatalogImageFileName(string $fileName): string
    {
        $trimmed = trim($fileName);
        $base = basename(str_replace('\\', '/', $trimmed));

        return '' === $base ? 'image' : $base;
    }

    private function map(CatalogItem $item, ?string $picnicProductId): CatalogItemView
    {
        $volume = $item->getVolume();
        $weight = $item->getWeight();
        $barcode = $item->getBarcode();
        $attributes = [];
        foreach ($item->getCatalogItemAttributes() as $attributeEntity) {
            $key = $attributeEntity->getAttribute();
            $attributes[] = new CatalogItemAttributeView(
                (string) $attributeEntity->getId(),
                null !== $key ? $key->value : '',
                $attributeEntity->getValue(),
            );
        }

        return new CatalogItemView(
            (string) $item->getId(),
            $item->getName(),
            $item->getImageFileName(),
            $volume?->getAmount(),
            $volume?->getUnit()->value,
            $weight?->getAmount(),
            $weight?->getUnit()->value,
            $barcode?->getCode(),
            $barcode?->getType(),
            $attributes,
            $picnicProductId,
        );
    }
}

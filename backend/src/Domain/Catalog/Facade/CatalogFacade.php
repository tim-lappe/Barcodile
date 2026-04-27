<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Facade;

use App\Domain\Catalog\CatalogImageContentType;
use App\Domain\Catalog\Entity\CatalogItem;
use App\Domain\Catalog\Entity\CatalogItemAttribute;
use App\Domain\Catalog\Entity\CatalogItemImage;
use App\Domain\Catalog\Repository\CatalogItemRepository;
use App\Domain\Picnic\Facade\PicnicFacade;
use App\Domain\Shared\Barcode as BarcodeValue;
use App\Domain\Shared\CatalogItemAttributeKey;
use App\Domain\Shared\Id\CatalogItemId;
use App\Domain\Shared\Volume;
use App\Domain\Shared\VolumeUnit;
use App\Domain\Shared\Weight;
use App\Domain\Shared\WeightUnit;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @SuppressWarnings("PHPMD.ExcessiveClassComplexity")
 * @SuppressWarnings("PHPMD.ExcessiveClassLength")
 */
final readonly class CatalogFacade
{
    public function __construct(
        private CatalogItemRepository $catalogItemRepo,
        private PicnicFacade $picnic,
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @return list<CatalogItemView>
     */
    public function listCatalogItems(int $page, int $itemsPerPage, string $orderNameDir, ?string $nameContains): array
    {
        $page = max(1, $page);
        $itemsPerPage = max(1, min(500, $itemsPerPage));
        $offset = ($page - 1) * $itemsPerPage;
        $rows = $this->catalogItemRepo->findPagedByNameOrder($offset, $itemsPerPage, $orderNameDir, $nameContains);
        $picnic = $this->picnic->mapProductIdsByCatalogItemIds(array_map(static fn (CatalogItem $item): string => (string) $item->getId(), $rows));

        return array_map(fn (CatalogItem $item): CatalogItemView => $this->map($item, $picnic[(string) $item->getId()] ?? null), $rows);
    }

    public function getCatalogItem(string $catalogItemId): CatalogItemView
    {
        $catalogItemIdObject = CatalogItemId::fromString($catalogItemId);

        return $this->map($this->mustFind($catalogItemIdObject), $this->picnic->productIdForCatalogItem($catalogItemId));
    }

    /**
     * @param list<array{rowId: string|null, attribute: string, value: mixed}>|null $attributes
     *
     * @SuppressWarnings("PHPMD.ExcessiveParameterList")
     */
    public function createCatalogItem(
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
    ): CatalogItemView {
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

        return $this->map($item, $this->picnic->productIdForCatalogItem((string) $item->getId()));
    }

    /**
     * @param list<array{rowId: string|null, attribute: string, value: mixed}>|null $attributes
     *
     * @SuppressWarnings("PHPMD.ExcessiveParameterList")
     * @SuppressWarnings("PHPMD.ExcessiveMethodLength")
     * @SuppressWarnings("PHPMD.CyclomaticComplexity")
     * @SuppressWarnings("PHPMD.NPathComplexity")
     */
    public function updateCatalogItem(
        string $catalogItemId,
        bool $nameSpecified,
        ?string $name,
        bool $volumeSpecified,
        ?string $volumeAmount,
        ?string $volumeUnit,
        bool $weightSpecified,
        ?string $weightAmount,
        ?string $weightUnit,
        bool $barcodeSpecified,
        ?string $barcodeCode,
        ?string $barcodeType,
        bool $attributesSpecified,
        ?array $attributes,
        bool $picnicLinkSpecified,
        ?string $picnicProductId,
    ): void {
        $item = $this->mustFind(CatalogItemId::fromString($catalogItemId));
        if ($nameSpecified && null !== $name) {
            $item->changeName($name);
        }
        if ($volumeSpecified) {
            $item->changeVolume($this->volumeInputToDomain($volumeAmount, $volumeUnit));
        }
        if ($weightSpecified) {
            $item->changeWeight($this->weightInputToDomain($weightAmount, $weightUnit));
        }
        if ($barcodeSpecified) {
            $this->applyBarcodeFromInput($item, $barcodeCode, $barcodeType);
        }
        if ($attributesSpecified) {
            $this->applyAttributeRows($item, $attributes);
        }
        if ($picnicLinkSpecified) {
            $this->picnic->syncProductLinkForCatalogItem((string) $item->getId(), $picnicProductId);
        }
        $this->catalogItemRepo->save($item);
    }

    public function deleteCatalogItem(string $catalogItemId): void
    {
        $this->catalogItemRepo->remove($this->mustFind(CatalogItemId::fromString($catalogItemId)));
    }

    public function uploadCatalogItemImage(string $catalogItemId, string $fileName, string $mimeType, string $binary): CatalogItemView
    {
        $item = $this->mustFind(CatalogItemId::fromString($catalogItemId));
        $safe = $this->sanitizeCatalogImageFileName($fileName);
        $contentType = CatalogImageContentType::tryFromMimeType($mimeType) ?? CatalogImageContentType::Jpeg;
        $item->assignImage($safe, $binary, $contentType);
        $this->catalogItemRepo->save($item);

        return $this->map($item, $this->picnic->productIdForCatalogItem($catalogItemId));
    }

    public function deleteCatalogItemImage(string $catalogItemId): CatalogItemView
    {
        $item = $this->mustFind(CatalogItemId::fromString($catalogItemId));
        if (null !== $item->getImageFileName() && '' !== $item->getImageFileName()) {
            $item->clearImage();
            $this->catalogItemRepo->save($item);
        }

        return $this->map($item, $this->picnic->productIdForCatalogItem($catalogItemId));
    }

    public function getCatalogItemImage(string $catalogItemId): CatalogItemImageView
    {
        $item = $this->catalogItemRepo->findWithCatalogItemImage(CatalogItemId::fromString($catalogItemId));
        if (!$item instanceof CatalogItem) {
            throw new NotFoundHttpException('Catalog item not found.');
        }

        return $this->catalogItemImageToView($this->requireStoredCatalogItemImage($item));
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
        $summary = $this->picnic->productSummary($productId);
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

    private function catalogItemImageToView(CatalogItemImage $image): CatalogItemImageView
    {
        $body = $image->getBody();
        $mime = $image->getContentType();

        return new CatalogItemImageView(
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

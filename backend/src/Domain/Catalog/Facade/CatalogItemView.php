<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Facade;

/**
 * @SuppressWarnings("PHPMD.ExcessiveParameterList")
 */
final readonly class CatalogItemView
{
    /**
     * @param list<CatalogItemAttributeView> $attributes
     */
    public function __construct(
        public string $resourceId,
        public string $name,
        public ?string $imageFileName,
        public ?string $volumeAmount,
        public ?string $volumeUnit,
        public ?string $weightAmount,
        public ?string $weightUnit,
        public ?string $barcodeCode,
        public ?string $barcodeType,
        public array $attributes,
        public ?string $picnicProductId,
    ) {
    }
}

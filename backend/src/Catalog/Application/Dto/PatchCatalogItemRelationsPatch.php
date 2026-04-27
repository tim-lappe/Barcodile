<?php

declare(strict_types=1);

namespace App\Catalog\Application\Dto;

final readonly class PatchCatalogItemRelationsPatch
{
    /**
     * @param list<CatalogItemAttributeRowInput>|null $attrs
     */
    public function __construct(
        public bool $attrsSpecified,
        public ?array $attrs,
        public bool $picnicLinkSpecified,
        public ?string $picnicProductId,
    ) {
    }
}

<?php

declare(strict_types=1);

namespace App\Application\Catalog\Dto;

use Symfony\Component\Serializer\Attribute\SerializedName;

final readonly class PicnicCatalogProductHintResponse
{
    public function __construct(
        #[SerializedName('id')]
        public string $resourceId,
        public string $name,
        public string $brand,
        public string $unitQuantity,
    ) {
    }
}

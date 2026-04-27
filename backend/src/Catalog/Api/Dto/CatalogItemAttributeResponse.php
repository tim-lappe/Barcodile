<?php

declare(strict_types=1);

namespace App\Catalog\Api\Dto;

use Symfony\Component\Serializer\Attribute\SerializedName;

final readonly class CatalogItemAttributeResponse
{
    public function __construct(
        #[SerializedName('id')]
        public string $resourceId,
        public string $attribute,
        public mixed $value,
    ) {
    }
}

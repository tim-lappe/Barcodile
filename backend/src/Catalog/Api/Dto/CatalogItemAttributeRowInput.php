<?php

declare(strict_types=1);

namespace App\Catalog\Api\Dto;

use Symfony\Component\Serializer\Attribute\SerializedName;

final readonly class CatalogItemAttributeRowInput
{
    public function __construct(
        #[SerializedName('id')]
        public ?string $rowId,
        public string $attribute,
        public mixed $value,
    ) {
    }
}

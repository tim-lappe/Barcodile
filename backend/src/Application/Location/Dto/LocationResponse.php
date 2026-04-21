<?php

declare(strict_types=1);

namespace App\Application\Location\Dto;

use Symfony\Component\Serializer\Attribute\SerializedName;

final readonly class LocationResponse
{
    public function __construct(
        #[SerializedName('id')]
        public string $resourceId,
        public string $name,
        public ?string $parent,
    ) {
    }
}

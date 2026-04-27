<?php

declare(strict_types=1);

namespace App\Cart\Application\Dto;

use Symfony\Component\Serializer\Attribute\SerializedName;

final readonly class CartProviderIndexEntryResponse
{
    public function __construct(
        #[SerializedName('id')]
        public string $providerId,
        public string $name,
        public int $lineCount,
        public string $createdAt,
    ) {
    }
}

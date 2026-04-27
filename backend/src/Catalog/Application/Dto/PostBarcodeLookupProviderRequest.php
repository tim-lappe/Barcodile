<?php

declare(strict_types=1);

namespace App\Catalog\Application\Dto;

final readonly class PostBarcodeLookupProviderRequest
{
    public function __construct(
        public string $label,
        public string $apiKey,
        public ?string $kind = null,
        public bool $enabled = true,
        public ?int $sortOrder = null,
    ) {
    }
}

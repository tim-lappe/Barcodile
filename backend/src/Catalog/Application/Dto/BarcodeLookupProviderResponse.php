<?php

declare(strict_types=1);

namespace App\Catalog\Application\Dto;

final readonly class BarcodeLookupProviderResponse
{
    public function __construct(
        public string $id,
        public string $kind,
        public string $label,
        public bool $enabled,
        public int $sortOrder,
        public bool $apiKeyStored,
    ) {
    }
}

<?php

declare(strict_types=1);

namespace App\Catalog\Application\Dto;

final readonly class PatchBarcodeLookupProviderRequest
{
    public function __construct(
        public bool $labelSpecified,
        public ?string $label,
        public bool $enabledSpecified,
        public mixed $enabled,
        public bool $sortOrderSpecified,
        public ?int $sortOrder,
        public bool $apiKeySpecified,
        public ?string $apiKey,
    ) {
    }
}

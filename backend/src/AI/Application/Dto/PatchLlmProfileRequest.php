<?php

declare(strict_types=1);

namespace App\AI\Application\Dto;

final readonly class PatchLlmProfileRequest
{
    public function __construct(
        public bool $kindSpecified,
        public ?string $kind,
        public bool $labelSpecified,
        public ?string $label,
        public bool $modelSpecified,
        public ?string $model,
        public bool $baseUrlSpecified,
        public ?string $baseUrl,
        public bool $enabledSpecified,
        public ?bool $enabled,
        public bool $sortOrderSpecified,
        public ?int $sortOrder,
        public bool $apiKeySpecified,
        public ?string $apiKey,
    ) {
    }
}

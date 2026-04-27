<?php

declare(strict_types=1);

namespace App\AI\Application\Dto;

final readonly class PostLlmProfileRequest
{
    public function __construct(
        public string $kind,
        public string $label,
        public string $model,
        public string $apiKey,
        public ?string $baseUrl = null,
        public ?bool $enabled = null,
        public ?int $sortOrder = null,
    ) {
    }
}

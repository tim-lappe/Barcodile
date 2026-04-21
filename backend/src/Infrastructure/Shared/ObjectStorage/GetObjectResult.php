<?php

declare(strict_types=1);

namespace App\Infrastructure\Shared\ObjectStorage;

final readonly class GetObjectResult
{
    public function __construct(
        public string $body,
        public ?string $contentType,
        public ?int $contentLength,
        public ?string $eTag,
    ) {
    }
}

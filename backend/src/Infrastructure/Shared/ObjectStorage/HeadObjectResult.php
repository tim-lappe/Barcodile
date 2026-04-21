<?php

declare(strict_types=1);

namespace App\Infrastructure\Shared\ObjectStorage;

final readonly class HeadObjectResult
{
    public function __construct(
        public ?string $contentType,
        public ?int $contentLength,
        public ?string $eTag,
    ) {
    }
}

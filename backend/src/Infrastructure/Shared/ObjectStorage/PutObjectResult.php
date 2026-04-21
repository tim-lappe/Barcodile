<?php

declare(strict_types=1);

namespace App\Infrastructure\Shared\ObjectStorage;

final readonly class PutObjectResult
{
    public function __construct(
        public ?string $eTag,
    ) {
    }
}

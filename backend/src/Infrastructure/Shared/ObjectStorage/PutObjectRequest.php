<?php

declare(strict_types=1);

namespace App\Infrastructure\Shared\ObjectStorage;

final readonly class PutObjectRequest
{
    public function __construct(
        public StorageBucket $bucket,
        public ObjectKey $key,
        public string $body,
        public string $contentType,
    ) {
    }
}

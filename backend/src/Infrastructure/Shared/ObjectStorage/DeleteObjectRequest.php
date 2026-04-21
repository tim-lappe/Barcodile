<?php

declare(strict_types=1);

namespace App\Infrastructure\Shared\ObjectStorage;

final readonly class DeleteObjectRequest
{
    public function __construct(
        public StorageBucket $bucket,
        public ObjectKey $key,
    ) {
    }
}

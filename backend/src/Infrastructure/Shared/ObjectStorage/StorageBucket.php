<?php

declare(strict_types=1);

namespace App\Infrastructure\Shared\ObjectStorage;

use InvalidArgumentException;

final readonly class StorageBucket
{
    public function __construct(public string $name)
    {
        if ('' === $name) {
            throw new InvalidArgumentException('Bucket name must not be empty.');
        }
    }
}

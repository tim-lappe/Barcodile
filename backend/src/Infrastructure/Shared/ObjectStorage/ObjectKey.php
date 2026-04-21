<?php

declare(strict_types=1);

namespace App\Infrastructure\Shared\ObjectStorage;

use InvalidArgumentException;

final readonly class ObjectKey
{
    public function __construct(public string $value)
    {
        if ('' === $value) {
            throw new InvalidArgumentException('Object key must not be empty.');
        }
        if (str_starts_with($value, '/')) {
            throw new InvalidArgumentException('Object key must not start with "/".');
        }
        if (str_contains($value, '..')) {
            throw new InvalidArgumentException('Object key must not contain "..".');
        }
    }
}

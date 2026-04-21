<?php

declare(strict_types=1);

namespace App\Infrastructure\Shared\ObjectStorage;

use RuntimeException;
use Throwable;

final class ObjectStorageException extends RuntimeException
{
    public static function wrap(string $message, Throwable $previous): self
    {
        return new self($message, 0, $previous);
    }
}

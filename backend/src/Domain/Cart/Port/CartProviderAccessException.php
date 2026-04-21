<?php

declare(strict_types=1);

namespace App\Domain\Cart\Port;

use RuntimeException;
use Throwable;

final class CartProviderAccessException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly int $httpStatus,
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}

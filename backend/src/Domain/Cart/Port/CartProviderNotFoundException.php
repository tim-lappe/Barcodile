<?php

declare(strict_types=1);

namespace App\Domain\Cart\Port;

use RuntimeException;

final class CartProviderNotFoundException extends RuntimeException
{
    public function __construct(
        public readonly string $providerId,
    ) {
        parent::__construct('Cart provider is not available.');
    }
}

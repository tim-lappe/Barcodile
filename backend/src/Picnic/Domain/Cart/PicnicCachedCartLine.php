<?php

declare(strict_types=1);

namespace App\Picnic\Domain\Cart;

use App\SharedKernel\Domain\Id\ShoppingCartLineId;
use DateTimeImmutable;

final readonly class PicnicCachedCartLine
{
    public function __construct(
        public ShoppingCartLineId $lineId,
        public int $quantity,
        public DateTimeImmutable $createdAt,
        public string $catalogItemId,
        public string $displayName,
    ) {
    }
}

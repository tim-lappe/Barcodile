<?php

declare(strict_types=1);

namespace App\Picnic\Domain\Cart;

use DateTimeImmutable;

final readonly class PicnicCachedCart
{
    /**
     * @param list<PicnicCachedCartLine> $lines
     */
    public function __construct(
        public string $viewId,
        public string $name,
        public DateTimeImmutable $createdAt,
        public array $lines,
    ) {
    }
}

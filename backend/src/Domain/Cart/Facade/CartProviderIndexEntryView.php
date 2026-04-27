<?php

declare(strict_types=1);

namespace App\Domain\Cart\Facade;

final readonly class CartProviderIndexEntryView
{
    public function __construct(
        public string $providerId,
        public string $name,
        public int $lineCount,
        public string $createdAt,
    ) {
    }
}

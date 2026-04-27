<?php

declare(strict_types=1);

namespace App\Domain\Cart\Facade;

final readonly class ShoppingCartView
{
    /**
     * @param list<ShoppingCartLineView> $lines
     */
    public function __construct(
        public string $resourceId,
        public ?string $name,
        public string $createdAt,
        public array $lines,
    ) {
    }
}

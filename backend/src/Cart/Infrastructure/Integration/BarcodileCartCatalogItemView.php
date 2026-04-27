<?php

declare(strict_types=1);

namespace App\Cart\Infrastructure\Integration;

use App\Cart\Domain\Port\CartItemInterface;

final readonly class BarcodileCartCatalogItemView implements CartItemInterface
{
    public function __construct(
        private string $catalogItemId,
        private string $catalogItemName,
    ) {
    }

    public function getId(): string
    {
        return $this->catalogItemId;
    }

    public function name(): string
    {
        return $this->catalogItemName;
    }
}

<?php

declare(strict_types=1);

namespace App\Domain\Inventory\Facade;

final readonly class CartStockAutomationRuleView
{
    public function __construct(
        public string $resourceId,
        public string $catalogItemId,
        public string $shoppingCartId,
        public int $stockBelow,
        public int $addQuantity,
        public bool $enabled,
        public string $createdAt,
    ) {
    }
}

<?php

declare(strict_types=1);

namespace App\Application\Inventory\Dto;

final readonly class PostCartStockAutomationRuleRequest
{
    public function __construct(
        public string $shoppingCart,
        public int $stockBelow,
        public int $addQuantity,
        public bool $enabled,
    ) {
    }
}

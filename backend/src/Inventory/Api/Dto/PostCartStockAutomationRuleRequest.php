<?php

declare(strict_types=1);

namespace App\Inventory\Api\Dto;

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

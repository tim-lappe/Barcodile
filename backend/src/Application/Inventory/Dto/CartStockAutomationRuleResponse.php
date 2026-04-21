<?php

declare(strict_types=1);

namespace App\Application\Inventory\Dto;

use Symfony\Component\Serializer\Attribute\SerializedName;

final readonly class CartStockAutomationRuleResponse
{
    public function __construct(
        #[SerializedName('id')]
        public string $resourceId,
        public string $catalogItem,
        public string $shoppingCart,
        public int $stockBelow,
        public int $addQuantity,
        public bool $enabled,
        public string $createdAt,
    ) {
    }
}

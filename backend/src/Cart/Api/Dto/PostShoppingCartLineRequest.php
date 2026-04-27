<?php

declare(strict_types=1);

namespace App\Cart\Api\Dto;

final readonly class PostShoppingCartLineRequest
{
    public function __construct(
        public string $shoppingCart,
        public string $catalogItem,
        public int $quantity,
    ) {
    }
}

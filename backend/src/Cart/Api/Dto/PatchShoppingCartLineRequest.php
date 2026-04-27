<?php

declare(strict_types=1);

namespace App\Cart\Api\Dto;

final readonly class PatchShoppingCartLineRequest
{
    public function __construct(
        public int $quantity,
    ) {
    }
}

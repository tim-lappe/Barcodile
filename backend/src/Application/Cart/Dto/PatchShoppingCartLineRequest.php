<?php

declare(strict_types=1);

namespace App\Application\Cart\Dto;

final readonly class PatchShoppingCartLineRequest
{
    public function __construct(
        public int $quantity,
    ) {
    }
}

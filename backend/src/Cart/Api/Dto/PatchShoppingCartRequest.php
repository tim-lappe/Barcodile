<?php

declare(strict_types=1);

namespace App\Cart\Api\Dto;

final readonly class PatchShoppingCartRequest
{
    public function __construct(
        public ?string $name,
    ) {
    }
}

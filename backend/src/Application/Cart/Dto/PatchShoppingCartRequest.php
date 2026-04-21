<?php

declare(strict_types=1);

namespace App\Application\Cart\Dto;

final readonly class PatchShoppingCartRequest
{
    public function __construct(
        public ?string $name,
    ) {
    }
}

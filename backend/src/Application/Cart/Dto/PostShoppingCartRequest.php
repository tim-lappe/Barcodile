<?php

declare(strict_types=1);

namespace App\Application\Cart\Dto;

final readonly class PostShoppingCartRequest
{
    public function __construct(
        public ?string $name = null,
    ) {
    }
}

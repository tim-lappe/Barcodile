<?php

declare(strict_types=1);

namespace App\Cart\Api\Dto;

final readonly class PostShoppingCartRequest
{
    public function __construct(
        public ?string $name = null,
    ) {
    }
}

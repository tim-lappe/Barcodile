<?php

declare(strict_types=1);

namespace App\Inventory\Application\Dto;

final readonly class PostLocationRequest
{
    public function __construct(
        public string $name,
        public ?string $parent = null,
    ) {
    }
}

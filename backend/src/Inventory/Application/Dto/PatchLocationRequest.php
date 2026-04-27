<?php

declare(strict_types=1);

namespace App\Inventory\Application\Dto;

final readonly class PatchLocationRequest
{
    public function __construct(
        public string $name,
        public ?string $parent,
    ) {
    }
}

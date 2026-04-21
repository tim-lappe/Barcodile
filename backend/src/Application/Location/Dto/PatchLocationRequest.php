<?php

declare(strict_types=1);

namespace App\Application\Location\Dto;

final readonly class PatchLocationRequest
{
    public function __construct(
        public string $name,
        public ?string $parent,
    ) {
    }
}

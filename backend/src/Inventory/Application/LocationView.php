<?php

declare(strict_types=1);

namespace App\Inventory\Application;

final readonly class LocationView
{
    public function __construct(
        public string $resourceId,
        public string $name,
        public ?string $parentId,
    ) {
    }
}

<?php

declare(strict_types=1);

namespace App\Application\Inventory\Dto;

final readonly class PatchCartStockAutomationRuleRequest
{
    public function __construct(
        public bool $cartInPatch,
        public ?string $cartIri,
        public bool $stockBelowSpecified,
        public ?int $stockBelow,
        public bool $addQuantitySpecified,
        public ?int $addQuantity,
        public bool $enabledSpecified,
        public ?bool $enabled,
    ) {
    }
}

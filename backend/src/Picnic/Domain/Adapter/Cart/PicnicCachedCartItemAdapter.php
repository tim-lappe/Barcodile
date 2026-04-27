<?php

declare(strict_types=1);

namespace App\Picnic\Domain\Adapter\Cart;

use App\Cart\Domain\Port\CartItemInterface;

final readonly class PicnicCachedCartItemAdapter implements CartItemInterface
{
    public function __construct(
        private string $catalogItemId,
        private string $displayName,
    ) {
    }

    public function getId(): string
    {
        return $this->catalogItemId;
    }

    public function name(): string
    {
        return $this->displayName;
    }
}

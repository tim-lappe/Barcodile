<?php

declare(strict_types=1);

namespace App\Domain\Picnic\Port;

use App\Domain\Picnic\Cart\PicnicCachedCart;

interface PicnicCartSessionPort
{
    /**
     * @param callable(array<mixed>): PicnicCachedCart $buildFromRaw
     */
    public function getCachedCartView(callable $buildFromRaw): PicnicCachedCart;

    /**
     * @return array<mixed>
     */
    public function fetchRawCart(): array;

    public function addProductToCart(string $productId, int $quantity): void;

    public function removeProductFromCart(string $productId, int $quantity): void;
}

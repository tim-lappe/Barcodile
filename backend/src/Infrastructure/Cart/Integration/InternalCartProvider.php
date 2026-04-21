<?php

declare(strict_types=1);

namespace App\Infrastructure\Cart\Integration;

use App\Domain\Cart\Port\CartProviderIndexContribution;
use App\Domain\Cart\Port\CartProviderInterface;
use App\Domain\Cart\Repository\ShoppingCartRepository;
use Generator;

final readonly class InternalCartProvider implements CartProviderIndexContribution, CartProviderInterface
{
    public function __construct(
        private ShoppingCartRepository $cartRepo,
    ) {
    }

    public function indexEntry(): null
    {
        return null;
    }

    public function carts(): Generator
    {
        foreach ($this->cartRepo->findPagedByCreatedAtDesc(0, \PHP_INT_MAX) as $cart) {
            yield new ManagedShoppingCart($cart, $this->cartRepo);
        }
    }
}

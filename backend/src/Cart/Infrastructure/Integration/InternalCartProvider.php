<?php

declare(strict_types=1);

namespace App\Cart\Infrastructure\Integration;

use App\Cart\Domain\Port\CartProviderIndexContribution;
use App\Cart\Domain\Port\CartProviderInterface;
use App\Cart\Domain\Repository\ShoppingCartRepository;
use App\Catalog\Domain\Repository\CatalogItemRepository;
use Generator;

final readonly class InternalCartProvider implements CartProviderIndexContribution, CartProviderInterface
{
    public function __construct(
        private ShoppingCartRepository $cartRepo,
        private CatalogItemRepository $catalogItemRepo,
    ) {
    }

    public function indexEntry(): null
    {
        return null;
    }

    public function carts(): Generator
    {
        foreach ($this->cartRepo->findPagedByCreatedAtDesc(0, \PHP_INT_MAX) as $cart) {
            yield new ManagedShoppingCart($cart, $this->cartRepo, $this->catalogItemRepo);
        }
    }
}

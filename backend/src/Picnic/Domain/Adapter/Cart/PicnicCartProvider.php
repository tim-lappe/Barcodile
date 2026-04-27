<?php

declare(strict_types=1);

namespace App\Picnic\Domain\Adapter\Cart;

use App\Cart\Domain\Port\CartProviderAccessException;
use App\Cart\Domain\Port\CartProviderIndexContribution;
use App\Cart\Domain\Port\CartProviderIndexEntry;
use App\Cart\Domain\Port\CartProviderInterface;
use Generator;

final readonly class PicnicCartProvider implements CartProviderIndexContribution, CartProviderInterface
{
    public const string ID = 'picnic';

    public function __construct(
        private PicnicRemoteCart $picnicRemoteCart,
    ) {
    }

    public function indexEntry(): ?CartProviderIndexEntry
    {
        $cart = $this->picnicRemoteCart;
        try {
            $lineCount = iterator_count($cart->listLines());
        } catch (CartProviderAccessException) {
            return null;
        }

        return new CartProviderIndexEntry(
            $cart->getId(),
            $cart->name(),
            $lineCount,
            $cart->createdAt(),
        );
    }

    public function carts(): Generator
    {
        yield $this->picnicRemoteCart;
    }
}

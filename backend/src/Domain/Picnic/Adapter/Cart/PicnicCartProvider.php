<?php

declare(strict_types=1);

namespace App\Domain\Picnic\Adapter\Cart;

use App\Domain\Cart\Port\CartProviderAccessException;
use App\Domain\Cart\Port\CartProviderIndexContribution;
use App\Domain\Cart\Port\CartProviderIndexEntry;
use App\Domain\Cart\Port\CartProviderInterface;
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

<?php

declare(strict_types=1);

namespace App\Picnic\Domain\Adapter\Cart;

use App\Cart\Domain\Port\CartItemInterface;
use App\Cart\Domain\Port\CartLineInterface;
use App\Picnic\Domain\Cart\PicnicCachedCartLine;
use App\SharedKernel\Domain\Id\ShoppingCartLineId;
use DateTimeImmutable;

final readonly class PicnicCachedCartLineAdapter implements CartLineInterface
{
    public function __construct(private PicnicCachedCartLine $line)
    {
    }

    public function getId(): ShoppingCartLineId
    {
        return $this->line->lineId;
    }

    public function quantity(): int
    {
        return $this->line->quantity;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->line->createdAt;
    }

    public function item(): CartItemInterface
    {
        return new PicnicCachedCartItemAdapter($this->line->catalogItemId, $this->line->displayName);
    }
}

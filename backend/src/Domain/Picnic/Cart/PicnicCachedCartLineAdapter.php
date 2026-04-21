<?php

declare(strict_types=1);

namespace App\Domain\Picnic\Cart;

use App\Domain\Cart\Entity\ShoppingCartLineId;
use App\Domain\Cart\Port\CartItemInterface;
use App\Domain\Cart\Port\CartLineInterface;
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

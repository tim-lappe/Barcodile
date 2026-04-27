<?php

declare(strict_types=1);

namespace App\Cart\Infrastructure\Integration;

use App\Cart\Domain\Port\CartItemInterface;
use App\Cart\Domain\Port\CartLineInterface;
use App\SharedKernel\Domain\Id\ShoppingCartLineId;
use DateTimeImmutable;

final readonly class BarcodileCartCatalogLineView implements CartLineInterface
{
    public function __construct(
        private ShoppingCartLineId $lineId,
        private int $quantity,
        private DateTimeImmutable $createdAt,
        private CartItemInterface $item,
    ) {
    }

    public function getId(): ShoppingCartLineId
    {
        return $this->lineId;
    }

    public function quantity(): int
    {
        return $this->quantity;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function item(): CartItemInterface
    {
        return $this->item;
    }
}

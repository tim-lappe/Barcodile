<?php

declare(strict_types=1);

namespace App\Infrastructure\Cart\Integration;

use App\Domain\Cart\Entity\ShoppingCartLineId;
use App\Domain\Cart\Port\CartItemInterface;
use App\Domain\Cart\Port\CartLineInterface;
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

<?php

declare(strict_types=1);

namespace App\Domain\Cart\Port;

use App\Domain\Cart\Entity\ShoppingCartLineId;
use DateTimeImmutable;

interface CartLineInterface
{
    public function getId(): ShoppingCartLineId;

    public function quantity(): int;

    public function createdAt(): DateTimeImmutable;

    public function item(): CartItemInterface;
}

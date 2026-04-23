<?php

declare(strict_types=1);

namespace App\Domain\Cart\Port;

use App\Domain\Shared\Id\ShoppingCartLineId;
use DateTimeImmutable;

interface CartLineInterface
{
    public function getId(): ShoppingCartLineId;

    public function quantity(): int;

    public function createdAt(): DateTimeImmutable;

    public function item(): CartItemInterface;
}

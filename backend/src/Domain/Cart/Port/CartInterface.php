<?php

declare(strict_types=1);

namespace App\Domain\Cart\Port;

use App\Domain\Cart\Entity\ShoppingCartLineId;
use App\Domain\Catalog\Entity\CatalogItem;
use DateTimeImmutable;
use Generator;

interface CartInterface
{
    public function getId(): string;

    public function name(): string;

    public function changeName(?string $name): void;

    public function createdAt(): DateTimeImmutable;

    /**
     * @return Generator<int, CartLineInterface>
     */
    public function listLines(): Generator;

    public function addItem(CatalogItem $catalogItem, int $quantity): void;

    public function removeLine(ShoppingCartLineId $lineId): void;

    public function changeLineQuantity(ShoppingCartLineId $lineId, int $quantity): void;
}

<?php

declare(strict_types=1);

namespace App\Infrastructure\Cart\Integration;

use App\Domain\Cart\Entity\ShoppingCart;
use App\Domain\Cart\Entity\ShoppingCartLineId;
use App\Domain\Cart\Port\CartInterface;
use App\Domain\Cart\Repository\ShoppingCartRepository;
use App\Domain\Catalog\Entity\CatalogItem;
use DateTimeImmutable;
use Generator;
use InvalidArgumentException;

final readonly class ManagedShoppingCart implements CartInterface
{
    public function __construct(
        private ShoppingCart $cart,
        private ShoppingCartRepository $cartRepo,
    ) {
    }

    public function getId(): string
    {
        return $this->cart->getId()->toUuid()->toRfc4122();
    }

    public function name(): string
    {
        $name = $this->cart->getName();
        if (null !== $name && '' !== trim($name)) {
            return $name;
        }

        return 'Barcodile cart';
    }

    public function changeName(?string $name): void
    {
        $this->cart->changeName($name);
        $this->cartRepo->save($this->cart);
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->cart->getCreatedAt();
    }

    public function listLines(): Generator
    {
        foreach ($this->cart->getLines() as $line) {
            $catalogItem = $line->getCatalogItem();
            if (null === $catalogItem) {
                continue;
            }
            $lineId = $line->getId();
            $itemId = $catalogItem->getId();
            $itemView = new BarcodileCartCatalogItemView($itemId->toUuid()->toRfc4122(), $catalogItem->getName());
            yield new BarcodileCartCatalogLineView(
                $lineId,
                $line->getQuantity(),
                $line->getCreatedAt(),
                $itemView,
            );
        }
    }

    public function addItem(CatalogItem $catalogItem, int $quantity): void
    {
        if ($quantity < 1) {
            throw new InvalidArgumentException('Quantity must be at least 1.');
        }
        $this->cart->mergeOrAddLineForCatalogItem($catalogItem, $quantity);
        $this->cartRepo->save($this->cart);
    }

    public function removeLine(ShoppingCartLineId $lineId): void
    {
        $this->cart->detachLineById($lineId);
        $this->cartRepo->save($this->cart);
    }

    public function changeLineQuantity(ShoppingCartLineId $lineId, int $quantity): void
    {
        if ($quantity < 1) {
            throw new InvalidArgumentException('Quantity must be at least 1.');
        }
        $this->cart->applyLineQuantityByLineId($lineId, $quantity);
        $this->cartRepo->save($this->cart);
    }
}

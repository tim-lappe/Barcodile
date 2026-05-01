<?php

declare(strict_types=1);

namespace App\Cart\Infrastructure\Integration;

use App\Cart\Domain\Entity\ShoppingCart;
use App\Cart\Domain\Port\CartInterface;
use App\Cart\Domain\Repository\ShoppingCartRepository;
use App\Catalog\Domain\Repository\CatalogItemRepository;
use App\SharedKernel\Domain\Id\CatalogItemId;
use App\SharedKernel\Domain\Id\ShoppingCartLineId;
use DateTimeImmutable;
use Generator;

final readonly class ManagedShoppingCart implements CartInterface
{
    public function __construct(
        private ShoppingCart $cart,
        private ShoppingCartRepository $cartRepo,
        private CatalogItemRepository $catalogItemRepo,
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
            $catalogItemId = $line->getCatalogItemId();
            if (null === $catalogItemId) {
                continue;
            }
            $catalogItem = $this->catalogItemRepo->find($catalogItemId);
            $displayName = null === $catalogItem ? (string) $catalogItemId : $catalogItem->getName();
            $lineId = $line->getId();
            $itemView = new BarcodileCartCatalogItemView($catalogItemId->toUuid()->toRfc4122(), $displayName);
            yield new BarcodileCartCatalogLineView(
                $lineId,
                $line->getQuantity(),
                $line->getCreatedAt(),
                $itemView,
            );
        }
    }

    public function addItem(CatalogItemId $catalogItemId, int $quantity): void
    {
        $this->cart->mergeOrAddLineForCatalogItem($catalogItemId, $quantity);
        $this->cartRepo->save($this->cart);
    }

    public function removeLine(ShoppingCartLineId $lineId): void
    {
        $this->cart->detachLineById($lineId);
        $this->cartRepo->save($this->cart);
    }

    public function changeLineQuantity(ShoppingCartLineId $lineId, int $quantity): void
    {
        $this->cart->applyLineQuantityByLineId($lineId, $quantity);
        $this->cartRepo->save($this->cart);
    }
}

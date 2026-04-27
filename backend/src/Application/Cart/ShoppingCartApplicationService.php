<?php

declare(strict_types=1);

namespace App\Application\Cart;

use App\Application\Cart\Dto\ShoppingCartLineResponse;
use App\Application\Cart\Dto\ShoppingCartResponse;
use App\Application\Catalog\CatalogItemApplicationService;
use App\Application\Catalog\Dto\CatalogItemResponse;
use App\Domain\Cart\Facade\CartCatalogItemView;
use App\Domain\Cart\Facade\CartFacade;
use App\Domain\Cart\Facade\ShoppingCartLineView;
use App\Domain\Cart\Facade\ShoppingCartView;

final readonly class ShoppingCartApplicationService
{
    public function __construct(
        private CartFacade $carts,
        private CatalogItemApplicationService $catalogItems,
    ) {
    }

    /**
     * @return list<ShoppingCartResponse>
     */
    public function listShoppingCarts(): array
    {
        return array_map(fn (ShoppingCartView $cart): ShoppingCartResponse => $this->mapCart($cart), $this->carts->listShoppingCarts());
    }

    public function getShoppingCart(string $cartId): ShoppingCartResponse
    {
        return $this->mapCart($this->carts->getShoppingCart($cartId));
    }

    public function createShoppingCart(?string $name): ShoppingCartResponse
    {
        return $this->mapCart($this->carts->createShoppingCart($name));
    }

    public function updateShoppingCartByRef(string $shoppingCartRef, ?string $name): void
    {
        $this->carts->updateShoppingCartByRef($shoppingCartRef, $name);
    }

    public function deleteShoppingCart(string $cartId): void
    {
        $this->carts->deleteShoppingCart($cartId);
    }

    public function createShoppingCartLine(string $shoppingCartRef, string $catalogItemId, int $quantity): ShoppingCartLineResponse
    {
        return $this->mapLine($this->carts->createShoppingCartLine($shoppingCartRef, $catalogItemId, $quantity));
    }

    public function updateShoppingCartLine(string $lineId, int $quantity): void
    {
        $this->carts->updateShoppingCartLine($lineId, $quantity);
    }

    public function deleteShoppingCartLine(string $lineId): void
    {
        $this->carts->deleteShoppingCartLine($lineId);
    }

    public function shoppingCartFromProvider(string $providerId): ShoppingCartResponse
    {
        return $this->mapCart($this->carts->shoppingCartFromProvider($providerId));
    }

    private function mapCart(ShoppingCartView $cart): ShoppingCartResponse
    {
        return new ShoppingCartResponse(
            $cart->resourceId,
            $cart->name,
            $cart->createdAt,
            array_map(fn (ShoppingCartLineView $line): ShoppingCartLineResponse => $this->mapLine($line), $cart->lines),
        );
    }

    private function mapLine(ShoppingCartLineView $line): ShoppingCartLineResponse
    {
        return new ShoppingCartLineResponse(
            $line->resourceId,
            $this->mapCatalogItem($line->catalogItem),
            $line->quantity,
            $line->createdAt,
        );
    }

    private function mapCatalogItem(CartCatalogItemView $item): CatalogItemResponse
    {
        if (null !== $item->catalogItem) {
            return $this->catalogItems->catalogItemResponse($item->catalogItem);
        }

        return $this->catalogItems->minimalCatalogItemResponse($item->resourceId, $item->name, null);
    }
}

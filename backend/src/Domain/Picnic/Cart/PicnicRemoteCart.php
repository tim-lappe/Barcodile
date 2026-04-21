<?php

declare(strict_types=1);

namespace App\Domain\Picnic\Cart;

use App\Domain\Cart\Entity\ShoppingCartLineId;
use App\Domain\Cart\Port\CartInterface;
use App\Domain\Cart\Port\CartProviderAccessException;
use App\Domain\Catalog\Entity\CatalogItem;
use App\Domain\Picnic\Port\PicnicCartSessionPort;
use App\Domain\Picnic\Repository\PicnicCatalogItemProductLinkRepository;
use App\Domain\Picnic\Repository\PicnicIntegrationSettingsRepository;
use DateTimeImmutable;
use Generator;

final readonly class PicnicRemoteCart implements CartInterface
{
    public function __construct(
        private PicnicCartSessionPort $picnicCartSession,
        private PicnicCartShoppingCartViewNormalizer $cartViewNormalizer,
        private PicnicCatalogItemProductLinkRepository $picnicLinkRepo,
        private PicnicIntegrationSettingsRepository $picnicSettingsRepo,
    ) {
    }

    public function getId(): string
    {
        return PicnicCartShoppingCartViewNormalizer::SHOPPING_CART_VIEW_ID;
    }

    public function name(): string
    {
        $settings = $this->picnicSettingsRepo->getSingleton();
        $custom = $settings->getCartDisplayName();
        if (null !== $custom && '' !== trim($custom)) {
            return trim($custom);
        }

        return $this->cachedCart()->name;
    }

    public function changeName(?string $name): void
    {
        $settings = $this->picnicSettingsRepo->getSingleton();
        $trimmed = null === $name ? null : trim($name);
        if ('' === $trimmed) {
            $trimmed = null;
        }
        $settings->changeCartDisplayName($trimmed);
        $this->picnicSettingsRepo->flush();
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->cachedCart()->createdAt;
    }

    public function listLines(): Generator
    {
        foreach ($this->cachedCart()->lines as $line) {
            yield new PicnicCachedCartLineAdapter($line);
        }
    }

    public function addItem(CatalogItem $catalogItem, int $quantity): void
    {
        if ($quantity < 1) {
            throw new CartProviderAccessException('Quantity must be at least 1.', 400);
        }
        $link = $this->picnicLinkRepo->findOneByCatalogItemId($catalogItem->getId());
        $productId = $link?->getProductId();
        if (null === $productId || '' === $productId) {
            throw new CartProviderAccessException('Catalog item is not linked to a Picnic product.', 400);
        }
        $this->picnicCartSession->addProductToCart($productId, $quantity);
    }

    public function removeLine(ShoppingCartLineId $lineId): void
    {
        $raw = $this->picnicCartSession->fetchRawCart();
        $ctx = $this->cartViewNormalizer->resolveLineMutationContext($lineId, $raw);
        if (null === $ctx) {
            throw new CartProviderAccessException('Cart line not found.', 404);
        }
        $productId = $ctx['productId'];
        if (null === $productId || '' === $productId) {
            throw new CartProviderAccessException('This cart line cannot be removed via the API.', 400);
        }
        $this->picnicCartSession->removeProductFromCart($productId, $ctx['quantity']);
    }

    public function changeLineQuantity(ShoppingCartLineId $lineId, int $quantity): void
    {
        if ($quantity < 1) {
            throw new CartProviderAccessException('Quantity must be at least 1.', 400);
        }
        $ctx = $this->lineMutationContextOrThrow($lineId);
        $this->applyQuantityDelta($ctx, $quantity);
    }

    /**
     * @return array{productId: string, quantity: int}
     */
    private function lineMutationContextOrThrow(ShoppingCartLineId $lineId): array
    {
        $raw = $this->picnicCartSession->fetchRawCart();
        $ctx = $this->cartViewNormalizer->resolveLineMutationContext($lineId, $raw);
        if (null === $ctx) {
            throw new CartProviderAccessException('Cart line not found.', 404);
        }
        $productId = $ctx['productId'];
        if (null === $productId || '' === $productId) {
            throw new CartProviderAccessException('This cart line quantity cannot be changed via the API.', 400);
        }

        return ['productId' => $productId, 'quantity' => $ctx['quantity']];
    }

    /**
     * @param array{productId: string, quantity: int} $ctx
     */
    private function applyQuantityDelta(array $ctx, int $quantity): void
    {
        $productId = $ctx['productId'];
        $current = $ctx['quantity'];
        $delta = $quantity - $current;
        if (0 === $delta) {
            return;
        }
        if ($delta > 0) {
            $this->picnicCartSession->addProductToCart($productId, $delta);

            return;
        }
        $this->picnicCartSession->removeProductFromCart($productId, -$delta);
    }

    private function cachedCart(): PicnicCachedCart
    {
        return $this->picnicCartSession->getCachedCartView(
            fn (array $raw): PicnicCachedCart => $this->cartViewNormalizer->normalize($raw)
        );
    }
}

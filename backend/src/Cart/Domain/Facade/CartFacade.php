<?php

declare(strict_types=1);

namespace App\Cart\Domain\Facade;

use App\Cart\Domain\Entity\ShoppingCart;
use App\Cart\Domain\Entity\ShoppingCartLine;
use App\Cart\Domain\Port\CartInterface;
use App\Cart\Domain\Port\CartLineInterface;
use App\Cart\Domain\Port\CartProviderAccessException;
use App\Cart\Domain\Port\CartProviderInterface;
use App\Cart\Domain\Port\CartProviderRegistry;
use App\Cart\Domain\Repository\ShoppingCartRepository;
use App\Catalog\Domain\Facade\CatalogFacade;
use App\SharedKernel\Domain\Id\CatalogItemId;
use App\SharedKernel\Domain\Id\ShoppingCartId;
use App\SharedKernel\Domain\Id\ShoppingCartLineId;
use DateTimeInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Uid\Uuid;

/**
 * @SuppressWarnings("PHPMD.TooManyMethods")
 * @SuppressWarnings("PHPMD.ExcessiveClassComplexity")
 * @SuppressWarnings("PHPMD.ExcessiveClassLength")
 */
final readonly class CartFacade
{
    public function __construct(
        private ShoppingCartRepository $cartRepo,
        private CartProviderRegistry $cartProviderRegistry,
        private CatalogFacade $catalog,
    ) {
    }

    /**
     * @return list<CartProviderIndexEntryView>
     */
    public function providerIndex(): array
    {
        $out = [];
        foreach ($this->cartProviderRegistry->indexEntries() as $entry) {
            $out[] = new CartProviderIndexEntryView(
                $entry->providerId,
                $entry->name,
                $entry->lineCount,
                $entry->createdAt->format(DateTimeInterface::ATOM),
            );
        }

        return $out;
    }

    /**
     * @return list<ShoppingCartView>
     */
    public function listShoppingCarts(): array
    {
        return array_map(
            fn (ShoppingCart $cart): ShoppingCartView => $this->mapStoredCart($cart),
            $this->cartRepo->findPagedByCreatedAtDesc(0, \PHP_INT_MAX),
        );
    }

    public function getShoppingCart(string $cartId): ShoppingCartView
    {
        return $this->mapStoredCart($this->mustFindCart(ShoppingCartId::fromString($cartId)));
    }

    public function createShoppingCart(?string $name): ShoppingCartView
    {
        $cart = new ShoppingCart();
        $cart->changeName($name);
        $this->cartRepo->save($cart);

        return $this->mapStoredCart($cart);
    }

    public function updateShoppingCartByRef(string $shoppingCartRef, ?string $name): void
    {
        if ($this->shoppingCartRefIsUuid($shoppingCartRef)) {
            $cart = $this->mustFindCart(ShoppingCartId::fromString($shoppingCartRef));
            $cart->changeName($name);
            $this->cartRepo->save($cart);

            return;
        }
        $this->applyProviderCartNameChange($shoppingCartRef, $name);
    }

    public function deleteShoppingCart(string $cartId): void
    {
        $this->cartRepo->remove($this->mustFindCart(ShoppingCartId::fromString($cartId)));
    }

    public function createShoppingCartLine(string $shoppingCartRef, string $catalogItemId, int $quantity): ShoppingCartLineView
    {
        if ($this->shoppingCartRefIsUuid($shoppingCartRef)) {
            return $this->createShoppingCartLineForStoredCart(
                ShoppingCartId::fromString($shoppingCartRef),
                CatalogItemId::fromString($catalogItemId),
                $quantity,
            );
        }

        return $this->addProviderLine($shoppingCartRef, CatalogItemId::fromString($catalogItemId), $quantity);
    }

    public function updateShoppingCartLine(string $lineId, int $quantity): void
    {
        $lineIdObject = ShoppingCartLineId::fromString($lineId);
        if ($this->tryUpdateStoredLineQuantity($lineIdObject, $quantity)) {
            return;
        }
        $this->updatePortableLineQuantity($lineIdObject, $quantity);
    }

    public function deleteShoppingCartLine(string $lineId): void
    {
        $lineIdObject = ShoppingCartLineId::fromString($lineId);
        if ($this->tryDeleteStoredLine($lineIdObject)) {
            return;
        }
        $this->deletePortableLine($lineIdObject);
    }

    public function shoppingCartFromProvider(string $providerId): ShoppingCartView
    {
        $provider = $this->cartProviderRegistry->get($providerId);
        if (null === $provider) {
            throw new NotFoundHttpException('Cart provider not found.');
        }
        foreach ($provider->carts() as $cart) {
            return $this->mapPortableCart($cart);
        }

        throw new NotFoundHttpException('No shopping cart for this provider.');
    }

    private function createShoppingCartLineForStoredCart(
        ShoppingCartId $shoppingCartId,
        CatalogItemId $catalogItemId,
        int $quantity,
    ): ShoppingCartLineView {
        $this->catalog->getCatalogItem((string) $catalogItemId);
        $cart = $this->mustFindCart($shoppingCartId);
        $line = $cart->mergeOrAddLineForCatalogItem($catalogItemId, $quantity);
        $this->cartRepo->save($cart);

        return $this->mapStoredLine($line);
    }

    private function addProviderLine(string $providerId, CatalogItemId $catalogItemId, int $quantity): ShoppingCartLineView
    {
        $provider = $this->requireCartProvider($providerId);
        foreach ($provider->carts() as $cart) {
            try {
                $cart->addItem($catalogItemId, $quantity);
            } catch (CartProviderAccessException $exception) {
                $this->throwCartProviderAccess($exception);
            }
            $matched = $this->matchLineAfterAdd($cart, $catalogItemId);
            if (null !== $matched) {
                return $matched;
            }

            throw new NotFoundHttpException('Shopping cart line not found after add.');
        }

        throw new NotFoundHttpException('No shopping cart for this provider.');
    }

    private function requireCartProvider(string $providerId): CartProviderInterface
    {
        $provider = $this->cartProviderRegistry->get($providerId);
        if (null === $provider) {
            throw new NotFoundHttpException('Shopping cart not found.');
        }

        return $provider;
    }

    private function matchLineAfterAdd(CartInterface $cart, CatalogItemId $catalogItemId): ?ShoppingCartLineView
    {
        try {
            foreach ($cart->listLines() as $line) {
                if ($line->item()->getId() === (string) $catalogItemId) {
                    return $this->mapPortableLine($line);
                }
            }
        } catch (CartProviderAccessException $exception) {
            $this->throwCartProviderAccess($exception);
        }

        return null;
    }

    private function applyProviderCartNameChange(string $shoppingCartRef, ?string $name): void
    {
        $provider = $this->cartProviderRegistry->get($shoppingCartRef);
        if (null === $provider) {
            throw new NotFoundHttpException('Shopping cart not found.');
        }
        foreach ($provider->carts() as $cart) {
            try {
                $cart->changeName($name);
            } catch (CartProviderAccessException $exception) {
                $this->throwCartProviderAccess($exception);
            }

            return;
        }

        throw new NotFoundHttpException('No shopping cart for this provider.');
    }

    private function updatePortableLineQuantity(ShoppingCartLineId $lineId, int $quantity): void
    {
        $portable = $this->findCartForLine($lineId);
        if (null === $portable) {
            throw new NotFoundHttpException('Shopping cart line not found.');
        }
        try {
            $portable->changeLineQuantity($lineId, $quantity);
        } catch (CartProviderAccessException $exception) {
            $this->throwCartProviderAccess($exception);
        }
    }

    private function deletePortableLine(ShoppingCartLineId $lineId): void
    {
        $portable = $this->findCartForLine($lineId);
        if (null === $portable) {
            throw new NotFoundHttpException('Shopping cart line not found.');
        }
        try {
            $portable->removeLine($lineId);
        } catch (CartProviderAccessException $exception) {
            $this->throwCartProviderAccess($exception);
        }
    }

    private function findCartForLine(ShoppingCartLineId $lineId): ?CartInterface
    {
        foreach ($this->cartProviderRegistry->providers() as $provider) {
            foreach ($provider->carts() as $cart) {
                if ($this->cartContainsLineId($cart, $lineId)) {
                    return $cart;
                }
            }
        }

        return null;
    }

    private function cartContainsLineId(CartInterface $cart, ShoppingCartLineId $lineId): bool
    {
        try {
            foreach ($cart->listLines() as $line) {
                if ($line->getId()->equals($lineId)) {
                    return true;
                }
            }
        } catch (CartProviderAccessException) {
            return false;
        }

        return false;
    }

    private function tryUpdateStoredLineQuantity(ShoppingCartLineId $lineId, int $quantity): bool
    {
        $line = $this->cartRepo->findLineById($lineId);
        if (!$line instanceof ShoppingCartLine) {
            return false;
        }
        $line->changeQuantity($quantity);
        $cart = $line->getShoppingCart();
        if (null === $cart) {
            throw new NotFoundHttpException('Shopping cart line not found.');
        }
        $this->cartRepo->save($cart);

        return true;
    }

    private function tryDeleteStoredLine(ShoppingCartLineId $lineId): bool
    {
        $line = $this->cartRepo->findLineById($lineId);
        if (!$line instanceof ShoppingCartLine) {
            return false;
        }
        $cart = $line->getShoppingCart();
        if (null === $cart) {
            throw new NotFoundHttpException('Shopping cart line not found.');
        }
        $cart->detachLineById($lineId);
        $this->cartRepo->save($cart);

        return true;
    }

    private function mustFindCart(ShoppingCartId $cartId): ShoppingCart
    {
        $cart = $this->cartRepo->find($cartId);
        if (!$cart instanceof ShoppingCart) {
            throw new NotFoundHttpException('Shopping cart not found.');
        }

        return $cart;
    }

    private function shoppingCartRefIsUuid(string $ref): bool
    {
        return Uuid::isValid($ref);
    }

    private function mapStoredCart(ShoppingCart $cart): ShoppingCartView
    {
        $lines = [];
        foreach ($cart->getLines() as $line) {
            $catalogItemId = $line->getCatalogItemId();
            if (null === $catalogItemId) {
                continue;
            }
            $lines[] = $this->mapStoredLine($line);
        }

        return new ShoppingCartView(
            (string) $cart->getId(),
            $cart->getName(),
            $cart->getCreatedAt()->format(DateTimeInterface::ATOM),
            $lines,
        );
    }

    private function mapStoredLine(ShoppingCartLine $line): ShoppingCartLineView
    {
        $catalogItemId = $line->getCatalogItemId();
        if (null === $catalogItemId) {
            throw new NotFoundHttpException('Catalog item not found.');
        }
        $catalogItem = $this->catalog->getCatalogItem((string) $catalogItemId);

        return new ShoppingCartLineView(
            (string) $line->getId(),
            new CartCatalogItemView($catalogItem->resourceId, $catalogItem->name, $catalogItem),
            $line->getQuantity(),
            $line->getCreatedAt()->format(DateTimeInterface::ATOM),
        );
    }

    private function mapPortableCart(CartInterface $cart): ShoppingCartView
    {
        $lines = [];
        foreach ($cart->listLines() as $line) {
            $lines[] = $this->mapPortableLine($line);
        }
        $cartName = $cart->name();

        return new ShoppingCartView(
            $cart->getId(),
            '' === $cartName ? null : $cartName,
            $cart->createdAt()->format(DateTimeInterface::ATOM),
            $lines,
        );
    }

    private function mapPortableLine(CartLineInterface $line): ShoppingCartLineView
    {
        $item = $line->item();

        return new ShoppingCartLineView(
            (string) $line->getId(),
            new CartCatalogItemView($item->getId(), $item->name(), null),
            $line->quantity(),
            $line->createdAt()->format(DateTimeInterface::ATOM),
        );
    }

    private function throwCartProviderAccess(CartProviderAccessException $exception): never
    {
        throw match ($exception->httpStatus) {
            400 => new BadRequestHttpException($exception->getMessage(), $exception),
            404 => new NotFoundHttpException($exception->getMessage(), $exception),
            default => new HttpException($exception->httpStatus, $exception->getMessage(), $exception),
        };
    }
}

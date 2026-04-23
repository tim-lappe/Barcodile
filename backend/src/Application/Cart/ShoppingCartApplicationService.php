<?php

declare(strict_types=1);

namespace App\Application\Cart;

use App\Application\Cart\Dto\ShoppingCartLineResponse;
use App\Application\Cart\Dto\ShoppingCartResponse;
use App\Domain\Cart\Entity\ShoppingCart;
use App\Domain\Cart\Entity\ShoppingCartLine;
use App\Domain\Cart\Port\CartInterface;
use App\Domain\Cart\Port\CartProviderRegistry;
use App\Domain\Cart\Repository\ShoppingCartRepository;
use App\Domain\Catalog\Entity\CatalogItem;
use App\Domain\Picnic\Repository\PicnicCatalogItemProductLinkRepository;
use App\Domain\Shared\Id\CatalogItemId;
use App\Domain\Shared\Id\ShoppingCartId;
use App\Domain\Shared\Id\ShoppingCartLineId;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Uid\Uuid;

final readonly class ShoppingCartApplicationService
{
    public function __construct(
        private ShoppingCartRepository $cartRepo,
        private PicnicCatalogItemProductLinkRepository $picnicLinkRepo,
        private CartProviderRegistry $cartProviderRegistry,
        private EntityManagerInterface $entityManager,
        private ShoppingCartExternalCartOperations $externalCartOps,
        private PortableShoppingCartMapper $portableMapper,
        private ShoppingCartEntityResponseMapper $entityResponseMapper,
        private ShoppingCartProviderLineAppender $providerLineAppender,
    ) {
    }

    /**
     * @return list<ShoppingCartResponse>
     */
    public function listShoppingCarts(): array
    {
        $rows = $this->cartRepo->findPagedByCreatedAtDesc(0, \PHP_INT_MAX);
        $out = [];
        foreach ($rows as $cart) {
            $out[] = $this->entityResponseMapper->mapShoppingCart($cart);
        }

        return $out;
    }

    public function getShoppingCart(ShoppingCartId $cartId): ShoppingCartResponse
    {
        $cart = $this->cartRepo->find($cartId);
        if (!$cart instanceof ShoppingCart) {
            throw new NotFoundHttpException('Shopping cart not found.');
        }

        return $this->entityResponseMapper->mapShoppingCart($cart);
    }

    public function createShoppingCart(?string $name): ShoppingCartResponse
    {
        $cart = new ShoppingCart();
        $cart->changeName($name);
        $this->cartRepo->save($cart);

        return $this->entityResponseMapper->mapShoppingCart($cart);
    }

    public function updateShoppingCart(ShoppingCartId $cartId, ?string $name): void
    {
        $cart = $this->mustFindCart($cartId);
        $cart->changeName($name);
        $this->cartRepo->save($cart);
    }

    public function updateShoppingCartByRef(string $shoppingCartRef, ?string $name): void
    {
        if ($this->shoppingCartRefIsUuid($shoppingCartRef)) {
            $this->updateShoppingCart(ShoppingCartId::fromString($shoppingCartRef), $name);

            return;
        }
        $this->externalCartOps->applyProviderCartNameChange($shoppingCartRef, $name);
    }

    public function deleteShoppingCart(ShoppingCartId $cartId): void
    {
        $cart = $this->mustFindCart($cartId);
        $this->cartRepo->remove($cart);
    }

    public function createShoppingCartLine(string $shoppingCartRef, CatalogItemId $catalogItemId, int $quantity): ShoppingCartLineResponse
    {
        if ($this->shoppingCartRefIsUuid($shoppingCartRef)) {
            return $this->createShoppingCartLineForStoredCart(
                ShoppingCartId::fromString($shoppingCartRef),
                $catalogItemId,
                $quantity,
            );
        }

        return $this->providerLineAppender->addAndMapLine($shoppingCartRef, $catalogItemId, $quantity);
    }

    public function updateShoppingCartLine(ShoppingCartLineId $lineId, int $quantity): void
    {
        if ($this->tryUpdateStoredLineQuantity($lineId, $quantity)) {
            return;
        }
        $this->externalCartOps->updatePortableLineQuantity($lineId, $quantity);
    }

    public function deleteShoppingCartLine(ShoppingCartLineId $lineId): void
    {
        if ($this->tryDeleteStoredLine($lineId)) {
            return;
        }
        $this->externalCartOps->deletePortableLine($lineId);
    }

    public function shoppingCartFromProvider(string $providerId): ShoppingCartResponse
    {
        $provider = $this->cartProviderRegistry->get($providerId);
        if (null === $provider) {
            throw new NotFoundHttpException('Cart provider not found.');
        }
        $first = null;
        foreach ($provider->carts() as $cart) {
            $first = $cart;
            break;
        }
        if (!$first instanceof CartInterface) {
            throw new NotFoundHttpException('No shopping cart for this provider.');
        }

        return $this->portableMapper->mapCart($first);
    }

    private function createShoppingCartLineForStoredCart(
        ShoppingCartId $shoppingCartId,
        CatalogItemId $catalogItemId,
        int $quantity,
    ): ShoppingCartLineResponse {
        $cart = $this->mustFindCart($shoppingCartId);
        $catalogItem = $this->entityManager->find(CatalogItem::class, $catalogItemId);
        if (!$catalogItem instanceof CatalogItem) {
            throw new NotFoundHttpException('Catalog item not found.');
        }
        $line = $cart->mergeOrAddLineForCatalogItem($catalogItem, $quantity);
        $this->cartRepo->save($cart);
        $picnic = $this->picnicLinkRepo->mapProductIdByCatalogItemId([$catalogItemId]);

        return $this->entityResponseMapper->mapLine($line, $catalogItem, $picnic[(string) $catalogItemId] ?? null);
    }

    private function shoppingCartRefIsUuid(string $ref): bool
    {
        return Uuid::isValid($ref);
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
}

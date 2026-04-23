<?php

declare(strict_types=1);

namespace App\Application\Cart;

use App\Application\Cart\Dto\ShoppingCartLineResponse;
use App\Domain\Cart\Port\CartInterface;
use App\Domain\Cart\Port\CartLineInterface;
use App\Domain\Cart\Port\CartProviderAccessException;
use App\Domain\Cart\Port\CartProviderInterface;
use App\Domain\Cart\Port\CartProviderRegistry;
use App\Domain\Catalog\Entity\CatalogItem;
use App\Domain\Picnic\Repository\PicnicCatalogItemProductLinkRepository;
use App\Domain\Shared\Id\CatalogItemId;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class ShoppingCartProviderLineAppender
{
    public function __construct(
        private CartProviderRegistry $cartProviderRegistry,
        private EntityManagerInterface $entityManager,
        private PicnicCatalogItemProductLinkRepository $picnicLinkRepo,
        private PortableShoppingCartMapper $portableMapper,
    ) {
    }

    public function addAndMapLine(string $providerId, CatalogItemId $catalogItemId, int $quantity): ShoppingCartLineResponse
    {
        $provider = $this->requireCartProvider($providerId);
        $catalogItem = $this->requireCatalogItem($catalogItemId);
        foreach ($provider->carts() as $cart) {
            $this->addItemToCart($cart, $catalogItem, $quantity);
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

    private function requireCatalogItem(CatalogItemId $catalogItemId): CatalogItem
    {
        $catalogItem = $this->entityManager->find(CatalogItem::class, $catalogItemId);
        if (!$catalogItem instanceof CatalogItem) {
            throw new NotFoundHttpException('Catalog item not found.');
        }

        return $catalogItem;
    }

    private function addItemToCart(CartInterface $cart, CatalogItem $catalogItem, int $quantity): void
    {
        try {
            $cart->addItem($catalogItem, $quantity);
        } catch (CartProviderAccessException $exception) {
            $this->portableMapper->throwCartProviderAccess($exception);
        }
    }

    private function matchLineAfterAdd(CartInterface $cart, CatalogItemId $catalogItemId): ?ShoppingCartLineResponse
    {
        $expectedPicnicItemId = $this->expectedPicnicItemId($catalogItemId);
        try {
            foreach ($cart->listLines() as $line) {
                $mapped = $this->mapLineIfMatch($line, $catalogItemId, $expectedPicnicItemId);
                if (null !== $mapped) {
                    return $mapped;
                }
            }
        } catch (CartProviderAccessException $exception) {
            $this->portableMapper->throwCartProviderAccess($exception);
        }

        return null;
    }

    private function expectedPicnicItemId(CatalogItemId $catalogItemId): ?string
    {
        $linkedProductId = $this->picnicLinkRepo->findOneByCatalogItemId($catalogItemId)?->getProductId();
        if (null === $linkedProductId || '' === $linkedProductId) {
            return null;
        }

        return 'picnic:'.$linkedProductId;
    }

    private function mapLineIfMatch(CartLineInterface $line, CatalogItemId $catalogItemId, ?string $expectedPicnicItemId): ?ShoppingCartLineResponse
    {
        if (!$this->lineMatchesPortableRequest($line, $catalogItemId, $expectedPicnicItemId)) {
            return null;
        }

        return $this->portableMapper->mapLineResponse($line);
    }

    private function lineMatchesPortableRequest(CartLineInterface $line, CatalogItemId $catalogItemId, ?string $expectedPicnicItemId): bool
    {
        if (null !== $expectedPicnicItemId && $line->item()->getId() === $expectedPicnicItemId) {
            return true;
        }

        return $line->item()->getId() === (string) $catalogItemId;
    }
}

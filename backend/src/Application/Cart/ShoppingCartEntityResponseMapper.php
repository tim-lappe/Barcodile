<?php

declare(strict_types=1);

namespace App\Application\Cart;

use App\Application\Cart\Dto\ShoppingCartLineResponse;
use App\Application\Cart\Dto\ShoppingCartResponse;
use App\Application\Catalog\CatalogItemApplicationService;
use App\Domain\Cart\Entity\ShoppingCart;
use App\Domain\Cart\Entity\ShoppingCartLine;
use App\Domain\Catalog\Entity\CatalogItem;
use App\Domain\Picnic\Repository\PicnicCatalogItemProductLinkRepository;
use App\Domain\Shared\Id\CatalogItemId;
use DateTimeInterface;

final readonly class ShoppingCartEntityResponseMapper
{
    public function __construct(
        private PicnicCatalogItemProductLinkRepository $picnicLinkRepo,
        private CatalogItemApplicationService $catalogItemSvc,
    ) {
    }

    public function mapShoppingCart(ShoppingCart $cart): ShoppingCartResponse
    {
        $picnic = $this->picnicLinkRepo->mapProductIdByCatalogItemId($this->collectCatalogItemIds($cart));
        $lines = [];
        foreach ($cart->getLines() as $line) {
            $catalogItem = $line->getCatalogItem();
            if (null === $catalogItem) {
                continue;
            }
            $lines[] = $this->mapLine($line, $catalogItem, $picnic[(string) $catalogItem->getId()] ?? null);
        }

        return new ShoppingCartResponse(
            (string) $cart->getId(),
            $cart->getName(),
            $cart->getCreatedAt()->format(DateTimeInterface::ATOM),
            $lines,
        );
    }

    /**
     * @return list<CatalogItemId>
     */
    private function collectCatalogItemIds(ShoppingCart $cart): array
    {
        $catalogIds = [];
        foreach ($cart->getLines() as $line) {
            $catalogItem = $line->getCatalogItem();
            if (null !== $catalogItem) {
                $catalogIds[] = $catalogItem->getId();
            }
        }

        return $catalogIds;
    }

    public function mapLine(ShoppingCartLine $line, CatalogItem $catalogItem, ?string $picnicProductId): ShoppingCartLineResponse
    {
        return new ShoppingCartLineResponse(
            (string) $line->getId(),
            $this->catalogItemSvc->toCatalogItemResponse($catalogItem, $picnicProductId),
            $line->getQuantity(),
            $line->getCreatedAt()->format(DateTimeInterface::ATOM),
        );
    }
}

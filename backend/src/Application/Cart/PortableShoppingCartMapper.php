<?php

declare(strict_types=1);

namespace App\Application\Cart;

use App\Application\Cart\Dto\ShoppingCartLineResponse;
use App\Application\Cart\Dto\ShoppingCartResponse;
use App\Application\Catalog\CatalogItemApplicationService;
use App\Domain\Cart\Entity\ShoppingCartLineId;
use App\Domain\Cart\Port\CartInterface;
use App\Domain\Cart\Port\CartLineInterface;
use App\Domain\Cart\Port\CartProviderAccessException;
use App\Domain\Cart\Port\CartProviderInterface;
use App\Domain\Cart\Port\CartProviderRegistry;
use DateTimeInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class PortableShoppingCartMapper
{
    public function __construct(
        private CatalogItemApplicationService $catalogItemSvc,
        private CartProviderRegistry $cartProviderRegistry,
    ) {
    }

    public function findCartForLine(ShoppingCartLineId $lineId): ?CartInterface
    {
        foreach ($this->cartProviderRegistry->providers() as $provider) {
            $cart = $this->firstCartContainingLine($provider, $lineId);
            if (null !== $cart) {
                return $cart;
            }
        }

        return null;
    }

    private function firstCartContainingLine(CartProviderInterface $provider, ShoppingCartLineId $lineId): ?CartInterface
    {
        foreach ($provider->carts() as $cart) {
            if ($this->cartContainsLineId($cart, $lineId)) {
                return $cart;
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

    public function mapLineResponse(CartLineInterface $line): ShoppingCartLineResponse
    {
        $item = $line->item();

        return new ShoppingCartLineResponse(
            (string) $line->getId(),
            $this->catalogItemSvc->minimalCatalogItemResponse(
                $item->getId(),
                $item->name(),
                null,
            ),
            $line->quantity(),
            $line->createdAt()->format(DateTimeInterface::ATOM),
        );
    }

    public function mapCart(CartInterface $cart): ShoppingCartResponse
    {
        $lines = [];
        foreach ($cart->listLines() as $line) {
            $item = $line->item();
            $lines[] = new ShoppingCartLineResponse(
                (string) $line->getId(),
                $this->catalogItemSvc->minimalCatalogItemResponse(
                    $item->getId(),
                    $item->name(),
                    null,
                ),
                $line->quantity(),
                $line->createdAt()->format(DateTimeInterface::ATOM),
            );
        }

        $cartName = $cart->name();

        return new ShoppingCartResponse(
            $cart->getId(),
            '' === $cartName ? null : $cartName,
            $cart->createdAt()->format(DateTimeInterface::ATOM),
            $lines,
        );
    }

    public function throwCartProviderAccess(CartProviderAccessException $exception): never
    {
        throw match ($exception->httpStatus) {
            400 => new BadRequestHttpException($exception->getMessage(), $exception),
            404 => new NotFoundHttpException($exception->getMessage(), $exception),
            default => new HttpException($exception->httpStatus, $exception->getMessage(), $exception),
        };
    }
}

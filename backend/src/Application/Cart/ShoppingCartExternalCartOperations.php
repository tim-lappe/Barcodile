<?php

declare(strict_types=1);

namespace App\Application\Cart;

use App\Domain\Cart\Port\CartProviderAccessException;
use App\Domain\Cart\Port\CartProviderRegistry;
use App\Domain\Shared\Id\ShoppingCartLineId;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class ShoppingCartExternalCartOperations
{
    public function __construct(
        private CartProviderRegistry $cartProviderRegistry,
        private PortableShoppingCartMapper $portableMapper,
    ) {
    }

    public function applyProviderCartNameChange(string $shoppingCartRef, ?string $name): void
    {
        $provider = $this->cartProviderRegistry->get($shoppingCartRef);
        if (null === $provider) {
            throw new NotFoundHttpException('Shopping cart not found.');
        }
        foreach ($provider->carts() as $cart) {
            try {
                $cart->changeName($name);
            } catch (CartProviderAccessException $exception) {
                $this->portableMapper->throwCartProviderAccess($exception);
            }

            return;
        }

        throw new NotFoundHttpException('No shopping cart for this provider.');
    }

    public function updatePortableLineQuantity(ShoppingCartLineId $lineId, int $quantity): void
    {
        $portable = $this->portableMapper->findCartForLine($lineId);
        if (null === $portable) {
            throw new NotFoundHttpException('Shopping cart line not found.');
        }
        try {
            $portable->changeLineQuantity($lineId, $quantity);
        } catch (CartProviderAccessException $exception) {
            $this->portableMapper->throwCartProviderAccess($exception);
        }
    }

    public function deletePortableLine(ShoppingCartLineId $lineId): void
    {
        $portable = $this->portableMapper->findCartForLine($lineId);
        if (null === $portable) {
            throw new NotFoundHttpException('Shopping cart line not found.');
        }
        try {
            $portable->removeLine($lineId);
        } catch (CartProviderAccessException $exception) {
            $this->portableMapper->throwCartProviderAccess($exception);
        }
    }
}

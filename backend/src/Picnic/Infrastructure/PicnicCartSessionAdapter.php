<?php

declare(strict_types=1);

namespace App\Picnic\Infrastructure;

use App\Cart\Domain\Port\CartProviderAccessException;
use App\Picnic\Domain\Cart\PicnicCachedCart;
use App\Picnic\Domain\Port\PicnicCartSessionPort;
use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

final readonly class PicnicCartSessionAdapter implements PicnicCartSessionPort
{
    public function __construct(
        private PicnicAuthenticatedClientFactory $picnicClientFactory,
        #[Autowire(service: 'cache.picnic_cart')]
        private CacheInterface $picnicCartCache,
    ) {
    }

    public function getCachedCartView(callable $buildFromRaw): PicnicCachedCart
    {
        $resolved = $this->picnicClientFactory->resolveConfigAndCacheKeyOrThrow();

        return $this->picnicCartCache->get($resolved['cacheKey'], function (ItemInterface $item) use ($resolved, $buildFromRaw): PicnicCachedCart {
            $item->expiresAfter(60);

            return $buildFromRaw($this->requestRawCartOrThrow($resolved['config']));
        });
    }

    /**
     * @return array<mixed>
     */
    public function fetchRawCart(): array
    {
        $resolved = $this->picnicClientFactory->resolveConfigAndCacheKeyOrThrow();

        return $this->requestRawCartOrThrow($resolved['config']);
    }

    public function addProductToCart(string $productId, int $quantity): void
    {
        $resolved = $this->picnicClientFactory->resolveConfigAndCacheKeyOrThrow();
        $client = $this->picnicClientFactory->createClient($resolved['config']);
        try {
            $client->cart->addProductToCart($productId, $quantity);
        } catch (RuntimeException $e) {
            throw new CartProviderAccessException($e->getMessage(), Response::HTTP_BAD_GATEWAY, 0, $e);
        }
        $this->picnicCartCache->delete($resolved['cacheKey']);
    }

    public function removeProductFromCart(string $productId, int $quantity): void
    {
        $resolved = $this->picnicClientFactory->resolveConfigAndCacheKeyOrThrow();
        $client = $this->picnicClientFactory->createClient($resolved['config']);
        try {
            $client->cart->removeProductFromCart($productId, $quantity);
        } catch (RuntimeException $e) {
            throw new CartProviderAccessException($e->getMessage(), Response::HTTP_BAD_GATEWAY, 0, $e);
        }
        $this->picnicCartCache->delete($resolved['cacheKey']);
    }

    /**
     * @return array<mixed>
     */
    private function requestRawCartOrThrow(PicnicApiConfig $config): array
    {
        $client = $this->picnicClientFactory->createClient($config);
        try {
            $cart = $client->cart->getCart();
        } catch (RuntimeException $e) {
            throw new CartProviderAccessException($e->getMessage(), Response::HTTP_BAD_GATEWAY, 0, $e);
        }
        if (!\is_array($cart)) {
            throw new CartProviderAccessException('Picnic returned an unexpected cart payload.', Response::HTTP_BAD_GATEWAY);
        }

        return $cart;
    }
}

<?php

declare(strict_types=1);

namespace App\Picnic\Infrastructure\Domains\Cart;

use App\Picnic\Infrastructure\PicnicHttpClient;
use App\Picnic\Infrastructure\PicnicHttpHeaderMode;

final class CartService
{
    public function __construct(private readonly PicnicHttpClient $http)
    {
    }

    public function getCart(): mixed
    {
        return $this->http->sendRequest('GET', '/cart');
    }

    /**
     * @param list<array<string, mixed>>|null $sellingUnitContexts
     */
    public function addProductToCart(string $productId, int $count = 1, ?array $sellingUnitContexts = null): mixed
    {
        $body = [
            'product_id' => $productId,
            'count' => $count,
        ];
        if (null !== $sellingUnitContexts) {
            $body['selling_unit_contexts'] = $sellingUnitContexts;
        }

        return $this->http->sendRequest('POST', '/cart/add_product', $body);
    }

    /**
     * @param list<array<string, mixed>>|null $sellingUnitContexts
     */
    public function removeProductFromCart(string $productId, int $count = 1, ?array $sellingUnitContexts = null): mixed
    {
        $body = [
            'product_id' => $productId,
            'count' => $count,
        ];
        if (null !== $sellingUnitContexts) {
            $body['selling_unit_contexts'] = $sellingUnitContexts;
        }

        return $this->http->sendRequest('POST', '/cart/remove_product', $body);
    }

    public function clearCart(): mixed
    {
        return $this->http->sendRequest('POST', '/cart/clear');
    }

    public function getDeliverySlots(): mixed
    {
        return $this->http->sendRequest('GET', '/cart/delivery_slots');
    }

    public function setDeliverySlot(string $slotId): mixed
    {
        return $this->http->sendRequest('POST', '/cart/set_delivery_slot', ['slot_id' => $slotId]);
    }

    public function getOrderStatus(string $orderId): mixed
    {
        return $this->http->sendRequest('GET', '/cart/checkout/order/'.rawurlencode($orderId).'/status');
    }

    public function removeGroupFromCart(string $groupId): mixed
    {
        return $this->http->sendRequest('POST', '/cart/remove_group', ['group_id' => $groupId]);
    }

    public function getMinimumOrderValue(): mixed
    {
        return $this->http->sendRequest('GET', '/user-slot-minimum-order-value/minimum', null, PicnicHttpHeaderMode::WithPicnicAgent);
    }

    public function confirmOrder(string $orderId): mixed
    {
        return $this->http->sendRequest('POST', '/cart/checkout/order/'.rawurlencode($orderId).'/confirm');
    }
}

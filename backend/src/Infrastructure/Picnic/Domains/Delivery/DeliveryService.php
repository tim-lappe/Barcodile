<?php

declare(strict_types=1);

namespace App\Infrastructure\Picnic\Domains\Delivery;

use App\Infrastructure\Picnic\PicnicHttpClient;
use App\Infrastructure\Picnic\PicnicHttpHeaderMode;

final class DeliveryService
{
    public function __construct(private readonly PicnicHttpClient $http)
    {
    }

    /**
     * @param list<string> $filter
     */
    public function getDeliveries(array $filter = []): mixed
    {
        return $this->http->sendRequest('POST', '/deliveries/summary', $filter);
    }

    public function getDelivery(string $deliveryId): mixed
    {
        return $this->http->sendRequest('GET', '/deliveries/'.rawurlencode($deliveryId));
    }

    public function getDeliveryPosition(string $deliveryId): mixed
    {
        return $this->http->sendRequest('GET', '/deliveries/'.rawurlencode($deliveryId).'/position', null, PicnicHttpHeaderMode::WithPicnicAgent);
    }

    public function getDeliveryScenario(string $deliveryId): mixed
    {
        return $this->http->sendRequest('GET', '/deliveries/'.rawurlencode($deliveryId).'/scenario', null, PicnicHttpHeaderMode::WithPicnicAgent);
    }

    public function cancelDelivery(string $deliveryId): mixed
    {
        return $this->http->sendRequest('POST', '/order/delivery/'.rawurlencode($deliveryId).'/cancel');
    }

    public function setDeliveryRating(string $deliveryId, int $rating): mixed
    {
        return $this->http->sendRequest('POST', '/deliveries/'.rawurlencode($deliveryId).'/rating', ['rating' => $rating]);
    }

    public function sendDeliveryInvoiceEmail(string $deliveryId): mixed
    {
        return $this->http->sendRequest('POST', '/deliveries/'.rawurlencode($deliveryId).'/resend_invoice_email');
    }
}

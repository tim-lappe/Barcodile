<?php

declare(strict_types=1);

namespace App\Picnic\Infrastructure\Domains\Payment;

use App\Picnic\Infrastructure\PicnicHttpClient;
use App\Picnic\Infrastructure\PicnicHttpHeaderMode;

final class PaymentService
{
    public function __construct(private readonly PicnicHttpClient $http)
    {
    }

    public function getPaymentProfile(): mixed
    {
        return $this->http->sendRequest('GET', '/payment-profile', null, PicnicHttpHeaderMode::WithPicnicAgent);
    }

    public function getWalletTransactions(int $pageNumber): mixed
    {
        return $this->http->sendRequest('POST', '/wallet/transactions', ['page_number' => $pageNumber]);
    }

    public function getWalletTransactionDetails(string $walletTransactionId): mixed
    {
        return $this->http->sendRequest('GET', '/wallet/transactions/'.rawurlencode($walletTransactionId));
    }
}

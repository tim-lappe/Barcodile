<?php

declare(strict_types=1);

namespace App\Infrastructure\Picnic\Domains\App;

use App\Infrastructure\Picnic\PicnicHttpClient;
use App\Infrastructure\Picnic\PicnicHttpHeaderMode;

final class AppService
{
    public function __construct(private readonly PicnicHttpClient $http)
    {
    }

    public function getBootstrapData(): mixed
    {
        return $this->http->sendRequest('GET', '/bootstrap');
    }

    public function getPage(string $pageId): mixed
    {
        return $this->http->sendRequest('GET', '/pages/'.$pageId, null, PicnicHttpHeaderMode::WithPicnicAgent);
    }

    public function resolveDeeplink(string $url): mixed
    {
        return $this->http->sendRequest('POST', '/deeplink/resolve', ['url' => $url], PicnicHttpHeaderMode::WithPicnicAgent);
    }
}

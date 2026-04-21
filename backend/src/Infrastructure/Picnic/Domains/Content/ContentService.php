<?php

declare(strict_types=1);

namespace App\Infrastructure\Picnic\Domains\Content;

use App\Infrastructure\Picnic\PicnicHttpClient;
use App\Infrastructure\Picnic\PicnicHttpHeaderMode;

final class ContentService
{
    public function __construct(private readonly PicnicHttpClient $http)
    {
    }

    public function getFaqContent(): mixed
    {
        return $this->http->sendRequest('GET', '/content/faq', null, PicnicHttpHeaderMode::WithPicnicAgent);
    }

    public function getSearchEmptyState(): mixed
    {
        return $this->http->sendRequest('GET', '/content/search_empty_state', null, PicnicHttpHeaderMode::WithPicnicAgent);
    }
}

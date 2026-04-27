<?php

declare(strict_types=1);

namespace App\Picnic\Infrastructure\Domains\Catalog;

use App\Picnic\Infrastructure\PicnicHttpBodyMode;
use App\Picnic\Infrastructure\PicnicHttpClient;
use App\Picnic\Infrastructure\PicnicHttpHeaderMode;
use App\Picnic\Infrastructure\PicnicImageSize;
use RuntimeException;

final class CatalogService
{
    public function __construct(private readonly PicnicHttpClient $http)
    {
    }

    /**
     * @return list<mixed>
     */
    public function search(string $query): array
    {
        $rawResults = $this->http->sendRequest(
            'GET',
            '/pages/search-page-results?search_term='.rawurlencode($query),
            null,
            PicnicHttpHeaderMode::WithPicnicAgent,
        );

        return CatalogJsonPathQuery::query($rawResults, '$..sellingUnit');
    }

    public function getSuggestions(string $query): mixed
    {
        return $this->http->sendRequest('GET', '/suggest?search_term='.rawurlencode($query));
    }

    public function getProductDetailsPage(string $productId): mixed
    {
        return $this->http->sendRequest(
            'GET',
            '/pages/product-details-page-root?id='.rawurlencode($productId).'&show_category_action=true&show_remove_from_purchases_page_action=true',
            null,
            PicnicHttpHeaderMode::WithPicnicAgent,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function getProductDetails(string $productId): array
    {
        $page = $this->getProductDetailsPage($productId);

        return CatalogProductDetailsExtractor::extract($productId, $page);
    }

    public function getImage(string $imageId, PicnicImageSize $size): string
    {
        $alternateRoute = $this->http->storefrontOrigin();

        $payload = $this->http->sendRequest(
            'GET',
            $alternateRoute.'/static/images/'.$imageId.'/'.$size->value.'.png',
            null,
            PicnicHttpHeaderMode::Base,
            PicnicHttpBodyMode::Raw,
        );
        if (!\is_string($payload)) {
            throw new RuntimeException('Picnic image response was not a binary payload.');
        }

        return $payload;
    }

    public function getImageAsDataUri(string $imageId, PicnicImageSize $size): string
    {
        $binary = $this->getImage($imageId, $size);

        return 'data:image/png;base64,'.base64_encode($binary);
    }
}

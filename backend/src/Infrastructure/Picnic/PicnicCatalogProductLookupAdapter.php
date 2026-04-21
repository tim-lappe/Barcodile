<?php

declare(strict_types=1);

namespace App\Infrastructure\Picnic;

use App\Domain\Picnic\Model\PicnicCatalogProductDetails;
use App\Domain\Picnic\Port\PicnicCatalogProductLookupPort;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class PicnicCatalogProductLookupAdapter implements PicnicCatalogProductLookupPort
{
    public function __construct(
        private PicnicApiConfigFactory $apiConfigFactory,
        private HttpClientInterface $httpClient,
    ) {
    }

    public function lookupByProductId(string $productId): PicnicCatalogProductDetails
    {
        $config = $this->apiConfigFactory->create();
        $client = new PicnicClient(
            $this->httpClient,
            $config,
            new PicnicAuthState(),
        );
        $details = $client->catalog->getProductDetails($productId);

        return new PicnicCatalogProductDetails(
            self::mixedToString($details['id'] ?? null) ?: $productId,
            self::mixedToString($details['name'] ?? null),
            self::mixedToString($details['brand'] ?? null),
            self::mixedToString($details['unitQuantity'] ?? null),
        );
    }

    private static function mixedToString(mixed $raw): string
    {
        if (\is_string($raw)) {
            return $raw;
        }
        if (null === $raw) {
            return '';
        }
        if (\is_scalar($raw)) {
            return (string) $raw;
        }

        return '';
    }
}

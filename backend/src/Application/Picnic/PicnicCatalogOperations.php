<?php

declare(strict_types=1);

namespace App\Application\Picnic;

use App\Application\Picnic\Dto\PicnicCatalogProductSummaryResponse;
use App\Application\Picnic\Dto\PicnicCatalogSearchHitResponse;
use App\Domain\Picnic\Port\PicnicCatalogProductLookupPort;
use App\Infrastructure\Picnic\PicnicApiConfigFactory;
use App\Infrastructure\Picnic\PicnicAuthState;
use App\Infrastructure\Picnic\PicnicClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class PicnicCatalogOperations
{
    public function __construct(
        private PicnicCatalogProductLookupPort $catalogLookup,
        private PicnicApiConfigFactory $apiConfigFactory,
        private HttpClientInterface $httpClient,
    ) {
    }

    /**
     * @return list<PicnicCatalogSearchHitResponse>
     */
    public function search(string $query): array
    {
        $units = $this->createCatalogClient()->catalog->search($query);
        $hits = [];
        foreach ($units as $unit) {
            $hit = $this->hitFromSearchUnit($unit);
            if (null !== $hit) {
                $hits[] = $hit;
            }
        }

        return $hits;
    }

    private function createCatalogClient(): PicnicClient
    {
        $config = $this->apiConfigFactory->create();

        return new PicnicClient(
            $this->httpClient,
            $config,
            new PicnicAuthState(),
        );
    }

    private function hitFromSearchUnit(mixed $unit): ?PicnicCatalogSearchHitResponse
    {
        if (!\is_array($unit)) {
            return null;
        }

        return new PicnicCatalogSearchHitResponse(
            self::mixedToString($unit['id'] ?? null),
            self::mixedToString($unit['name'] ?? null),
            self::optionalStringField($unit, 'image_id'),
            self::optionalIntFromNumericField($unit, 'display_price'),
            self::optionalScalarStringField($unit, 'unit_quantity'),
        );
    }

    /**
     * @param array<mixed> $unit
     */
    private static function optionalStringField(array $unit, string $key): ?string
    {
        $value = $unit[$key] ?? null;

        return \is_string($value) ? $value : null;
    }

    /**
     * @param array<mixed> $unit
     */
    private static function optionalIntFromNumericField(array $unit, string $key): ?int
    {
        $value = $unit[$key] ?? null;

        return is_numeric($value) ? (int) $value : null;
    }

    /**
     * @param array<mixed> $unit
     */
    private static function optionalScalarStringField(array $unit, string $key): ?string
    {
        $value = $unit[$key] ?? null;

        return \is_scalar($value) ? (string) $value : null;
    }

    public function productSummary(string $productId): PicnicCatalogProductSummaryResponse
    {
        $summary = $this->catalogLookup->lookupByProductId($productId);

        return new PicnicCatalogProductSummaryResponse(
            $summary->productId,
            $summary->name,
            $summary->brand,
            $summary->unitQuantity,
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

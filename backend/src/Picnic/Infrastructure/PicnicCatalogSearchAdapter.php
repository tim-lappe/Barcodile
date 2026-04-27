<?php

declare(strict_types=1);

namespace App\Picnic\Infrastructure;

use App\Picnic\Domain\Port\PicnicCatalogSearchPort;
use App\Picnic\Domain\ValueObject\PicnicCatalogSearchUnit;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class PicnicCatalogSearchAdapter implements PicnicCatalogSearchPort
{
    public function __construct(
        private PicnicApiConfigFactory $apiConfigFactory,
        private HttpClientInterface $httpClient,
    ) {
    }

    public function search(string $query): array
    {
        $config = $this->apiConfigFactory->create();
        $client = new PicnicClient(
            $this->httpClient,
            $config,
            new PicnicAuthState(),
        );
        $units = $client->catalog->search($query);
        $hits = [];
        foreach ($units as $unit) {
            $hit = $this->hitFromSearchUnit($unit);
            if (null !== $hit) {
                $hits[] = $hit;
            }
        }

        return $hits;
    }

    private function hitFromSearchUnit(mixed $unit): ?PicnicCatalogSearchUnit
    {
        if (!\is_array($unit)) {
            return null;
        }

        return new PicnicCatalogSearchUnit(
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

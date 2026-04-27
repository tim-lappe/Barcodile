<?php

declare(strict_types=1);

namespace App\Catalog\Infrastructure\BarcodeLookup;

use App\Catalog\Domain\BarcodeLookupDriverResult;
use App\Catalog\Domain\BarcodeLookupProviderKind;
use App\Catalog\Domain\Port\BarcodeLookupDriver;
use App\Catalog\Domain\ResolvedBarcodeProduct;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;

final readonly class BarcodeLookupComV3Driver implements BarcodeLookupDriver
{
    private const string BASE_URL = 'https://api.barcodelookup.com/v3/products';

    public function __construct(
        private HttpClientInterface $httpClient,
    ) {
    }

    public function supports(BarcodeLookupProviderKind $kind): bool
    {
        return match ($kind) {
            BarcodeLookupProviderKind::BarcodeLookupComV3 => true,
        };
    }

    public function lookup(string $apiKey, string $barcode): BarcodeLookupDriverResult
    {
        $trimmedKey = trim($apiKey);
        $trimmedBarcode = trim($barcode);
        if ('' === $trimmedKey || '' === $trimmedBarcode) {
            return BarcodeLookupDriverResult::notFound();
        }

        try {
            $response = $this->httpClient->request('GET', self::BASE_URL, [
                'query' => [
                    'barcode' => $trimmedBarcode,
                    'key' => $trimmedKey,
                ],
                'timeout' => 20,
            ]);
            $status = $response->getStatusCode();
            if ($status < 200 || $status >= 300) {
                return BarcodeLookupDriverResult::notFound();
            }
            $content = $response->getContent(false);
        } catch (ExceptionInterface) {
            return BarcodeLookupDriverResult::notFound();
        }

        try {
            /** @var array<string, mixed> $data */
            $data = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return BarcodeLookupDriverResult::notFound();
        }

        $products = $data['products'] ?? null;
        if (!\is_array($products) || [] === $products) {
            return BarcodeLookupDriverResult::notFound();
        }

        $first = $products[0];
        $productRow = $this->coerceToAssocArray($first);
        if (null === $productRow) {
            return BarcodeLookupDriverResult::notFound();
        }

        $title = $this->readNonEmptyString($productRow, 'title')
            ?? $this->readNonEmptyString($productRow, 'product_name');
        if (null === $title) {
            return BarcodeLookupDriverResult::notFound();
        }

        $brand = $this->readOptionalString($productRow, 'brand')
            ?? $this->readOptionalString($productRow, 'manufacturer');
        $category = $this->readOptionalString($productRow, 'category');
        $barcodeNumber = $this->readOptionalString($productRow, 'barcode_number');
        $barcodeFormats = $this->readOptionalString($productRow, 'barcode_formats');
        $imageUrl = $this->extractFirstImageUrl($productRow);

        return BarcodeLookupDriverResult::success(new ResolvedBarcodeProduct(
            $title,
            $brand,
            $imageUrl,
            $category,
            $barcodeNumber,
            $barcodeFormats,
        ));
    }

    /**
     * @param array<string, mixed> $row
     */
    private function readNonEmptyString(array $row, string $key): ?string
    {
        $v = $row[$key] ?? null;
        if (!\is_string($v)) {
            return null;
        }
        $t = trim($v);

        return '' !== $t ? $t : null;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function readOptionalString(array $row, string $key): ?string
    {
        $v = $row[$key] ?? null;
        if (!\is_string($v)) {
            return null;
        }
        $t = trim($v);

        return '' !== $t ? $t : null;
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return list<array<string, mixed>>
     */
    private function readImagesList(array $row): array
    {
        $images = $row['images'] ?? null;
        if (!\is_array($images)) {
            return [];
        }
        $out = [];
        foreach ($images as $item) {
            $coerced = $this->coerceToAssocArray($item);
            if (null !== $coerced) {
                $out[] = $coerced;
            }
        }

        return $out;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function coerceToAssocArray(mixed $value): ?array
    {
        if (!\is_array($value)) {
            return null;
        }
        $out = [];
        foreach ($value as $k => $v) {
            if (!\is_string($k)) {
                return null;
            }
            $out[$k] = $v;
        }

        return $out;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function extractFirstImageUrl(array $row): ?string
    {
        foreach ($this->readImagesList($row) as $img) {
            $url = $img['url'] ?? null;
            if (\is_string($url)) {
                $t = trim($url);
                if ('' !== $t) {
                    return $t;
                }
            }
        }

        return null;
    }
}

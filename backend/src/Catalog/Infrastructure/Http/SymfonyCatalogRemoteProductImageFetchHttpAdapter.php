<?php

declare(strict_types=1);

namespace App\Catalog\Infrastructure\Http;

use App\Catalog\Domain\CatalogImageContentType;
use App\Catalog\Domain\CatalogRemoteProductImageFetchResult;
use App\Catalog\Domain\Port\CatalogRemoteProductImageFetchPort;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class SymfonyCatalogRemoteProductImageFetchHttpAdapter implements CatalogRemoteProductImageFetchPort
{
    private const int MAX_BYTES = 5242880;

    public function __construct(
        private HttpClientInterface $httpClient,
    ) {
    }

    public function tryFetch(string $httpsUrl): ?CatalogRemoteProductImageFetchResult
    {
        $lower = strtolower($httpsUrl);
        if (!str_starts_with($lower, 'https://') && !str_starts_with($lower, 'http://')) {
            return null;
        }
        if (false === filter_var($httpsUrl, \FILTER_VALIDATE_URL)) {
            return null;
        }
        $host = parse_url($httpsUrl, \PHP_URL_HOST);
        if (!\is_string($host) || '' === $host) {
            return null;
        }
        $hostLower = strtolower($host);
        $blocked = [
            'localhost',
            '127.0.0.1',
            '0.0.0.0',
            '::1',
            'metadata.google.internal',
            '169.254.169.254',
        ];
        if (\in_array($hostLower, $blocked, true)) {
            return null;
        }

        try {
            $response = $this->httpClient->request('GET', $httpsUrl, [
                'timeout' => 20,
                'max_redirects' => 5,
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (compatible; BarcodileCatalogBot/1.0; +https://barcodile.local)',
                    'Accept' => 'image/avif,image/webp,image/apng,image/*,*/*;q=0.8',
                ],
            ]);
            if (200 !== $response->getStatusCode()) {
                return null;
            }
            $body = $response->getContent();
        } catch (ExceptionInterface) {
            return null;
        }

        if (\strlen($body) > self::MAX_BYTES || '' === $body) {
            return null;
        }

        $mime = '';
        if ($response->getHeaders()['content-type'] ?? []) {
            $mime = trim((string) $response->getHeaders()['content-type'][0]);
            $semi = strpos($mime, ';');
            if (false !== $semi) {
                $mime = trim(substr($mime, 0, $semi));
            }
        }
        $contentType = CatalogImageContentType::tryFromMimeType($mime);
        if (null === $contentType) {
            $contentType = CatalogImageContentType::tryDetectFromImageBinary($body);
        }
        if (null === $contentType) {
            $contentType = CatalogImageContentType::tryFromUrlPath($httpsUrl);
        }
        if (null === $contentType) {
            return null;
        }

        $path = parse_url($httpsUrl, \PHP_URL_PATH);
        $base = \is_string($path) ? basename(str_replace('\\', '/', $path)) : '';
        $trimmedBase = trim($base);
        if ('' === $trimmedBase || '.' === $trimmedBase || '..' === $trimmedBase) {
            $trimmedBase = 'product.'.$contentType->preferredExtension();
        }

        return new CatalogRemoteProductImageFetchResult($body, $contentType, $trimmedBase);
    }
}

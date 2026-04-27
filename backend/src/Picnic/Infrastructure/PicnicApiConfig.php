<?php

declare(strict_types=1);

namespace App\Picnic\Infrastructure;

use InvalidArgumentException;

final readonly class PicnicApiConfig
{
    public string $countryCode;

    public string $apiVersion;

    public ?string $authKey;

    public ?string $url;

    public function __construct(
        string $countryCode = 'NL',
        string $apiVersion = '15',
        ?string $authKey = null,
        ?string $url = null,
    ) {
        $normalizedAuthKey = self::normalizeOptionalNonEmpty($authKey);
        $normalizedUrl = self::normalizeOptionalNonEmpty($url);
        self::assertCountryAllowedWithoutUrl($countryCode, $normalizedUrl);
        $this->countryCode = $countryCode;
        $this->apiVersion = $apiVersion;
        $this->authKey = $normalizedAuthKey;
        $this->url = $normalizedUrl;
    }

    private static function normalizeOptionalNonEmpty(?string $value): ?string
    {
        return null !== $value && '' !== $value ? $value : null;
    }

    private static function assertCountryAllowedWithoutUrl(string $countryCode, ?string $normalizedUrl): void
    {
        if (null === $normalizedUrl && !\in_array($countryCode, ['NL', 'DE'], true)) {
            throw new InvalidArgumentException('countryCode must be NL or DE when url is not set.');
        }
    }

    public function resolveUrl(): string
    {
        if (null !== $this->url) {
            return $this->url;
        }

        return \sprintf(
            'https://storefront-prod.%s.picnicinternational.com/api/%s',
            strtolower($this->countryCode),
            $this->apiVersion,
        );
    }
}

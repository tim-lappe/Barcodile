<?php

declare(strict_types=1);

namespace App\Catalog\Infrastructure\BarcodeLookup;

use App\AI\Domain\Exception\OpenAiResponsesWebSearchException;
use App\AI\Domain\Port\OpenAiResponsesWebSearchPort;
use App\Catalog\Domain\BarcodeLookup\BarcodeCatalogLookupDraft;
use App\Catalog\Domain\BarcodeLookup\BarcodeCatalogLookupDraftExtras;
use App\Catalog\Domain\Port\BarcodeCatalogLookupProvider;
use App\SharedKernel\Domain\Barcode;
use App\SharedKernel\Domain\VolumeUnit;
use App\SharedKernel\Domain\WeightUnit;
use ValueError;

final readonly class LlmWebSearchBarcodeCatalogLookupProvider implements BarcodeCatalogLookupProvider
{
    private const string PROVIDER_ID = 'llm_web_search';

    private const string JSON_SCHEMA_NAME = 'barcodile_barcode_catalog_lookup';

    /**
     * @var array<string, mixed>
     */
    private const array RESPONSE_JSON_SCHEMA = [
        'type' => 'object',
        'additionalProperties' => false,
        'properties' => [
            'name' => [
                'type' => 'string',
                'description' => 'Consumer product title as sold at retail, without redundant barcode text.',
            ],
            'volume_amount' => [
                'anyOf' => [
                    ['type' => 'string'],
                    ['type' => 'null'],
                ],
                'description' => 'Numeric volume amount as a string, or null if unknown.',
            ],
            'volume_unit' => [
                'anyOf' => [
                    ['type' => 'string', 'enum' => ['ml', 'l']],
                    ['type' => 'null'],
                ],
                'description' => 'ml or l when volume is known, else null.',
            ],
            'weight_amount' => [
                'anyOf' => [
                    ['type' => 'string'],
                    ['type' => 'null'],
                ],
                'description' => 'Numeric net weight amount as a string, or null if unknown.',
            ],
            'weight_unit' => [
                'anyOf' => [
                    ['type' => 'string', 'enum' => ['g', 'kg']],
                    ['type' => 'null'],
                ],
                'description' => 'g or kg when weight is known, else null.',
            ],
            'alcohol_percent' => [
                'anyOf' => [
                    ['type' => 'number'],
                    ['type' => 'null'],
                ],
                'description' => 'ABV in percent if clearly stated for alcoholic beverages, else null.',
            ],
            'picnic_product_id' => [
                'anyOf' => [
                    ['type' => 'string'],
                    ['type' => 'null'],
                ],
                'description' => 'Picnic NL product or offer id if clearly stated for this exact barcode on Picnic or an authoritative source; otherwise null. Never guess.',
            ],
            'product_image_url' => [
                'anyOf' => [
                    ['type' => 'string'],
                    ['type' => 'null'],
                ],
                'description' => <<<'DESC'
Must be null unless you copy a URL from web_search results that loads a single product photo file (binary image), not an HTML document.

ACCEPT only if the URL clearly points at image bytes, for example:
- Path ends with .jpg .jpeg .png .webp or .gif (query string after the extension is OK).
- Or the URL is an obvious static image/CDN link from the same listing (e.g. paths containing /images/, /img/, /cdn/, /media/, /assets/, /static/, /is/image/, or well-known image hosts such as media-amazon, images-amazon, cloudinary, scene7, imgix, akamai, shopify CDN).

REJECT (use null instead):
- Any retailer product page, category page, or search page (including paths like /p/, /dp/, /product/, /item/, /pd/, /artikel/ when there is no image filename extension).
- URLs ending in .html .htm .php .aspx or with no path beyond the site root.
- og:url or canonical product page URLs unless they are explicitly a direct image asset URL as above.

If you cannot find a direct image asset URL that satisfies ACCEPT, return null. Never invent or guess URLs.
DESC,
            ],
        ],
        'required' => [
            'name',
            'volume_amount',
            'volume_unit',
            'weight_amount',
            'weight_unit',
            'alcohol_percent',
            'picnic_product_id',
            'product_image_url',
        ],
    ];

    public function __construct(
        private OpenAiResponsesWebSearchPort $openAiWebSearch,
    ) {
    }

    public function lookup(Barcode $barcode): BarcodeCatalogLookupDraft
    {
        $system = <<<'TXT'
You identify consumer packaged goods from barcodes (GTIN, EAN, UPC). Use the web_search tool to find authoritative product pages before answering. Prefer manufacturer or major retailer listings. If multiple products match, pick the most common retail SKU for that barcode. Never invent a barcode-specific match when sources are unclear: still return the best-effort name from packaging cues in search snippets, and use null for unknown numeric fields.

For picnic_product_id: return a Picnic NL product or offer id only when sources explicitly tie it to this barcode; otherwise null. Never guess.

For product_image_url (critical):
- Return a string only when web_search gives you a URL that loads raw image bytes (JPEG, PNG, WebP, or GIF), not an HTML page.
- Prefer URLs whose path ends with .jpg .jpeg .png .webp or .gif (anything after ? in the URL is fine).
- You may also use a URL from search snippets that is clearly a CDN/static image path (e.g. contains /images/, /img/, /cdn/, /media/, /is/image/, or image hostnames like media-amazon.com) as long as it is still a direct image file URL, not a product detail page.
- Do NOT return the main product page URL, Amazon /dp/ or /gp/product/ page links, or any URL that would render HTML when opened.
- If the only URLs you find are HTML storefront pages, return null for product_image_url.
- Never fabricate URLs.
TXT;

        $user = \sprintf(
            <<<'USR'
Barcode type: %s
Barcode code: %s

Return only the JSON object matching the schema (no markdown).

For product_image_url: copy a direct image file URL from your web_search results (path should end with .jpg .jpeg .png .webp or .gif when possible). If you only see normal product or article pages and no direct image asset link, set product_image_url to null.
USR,
            $barcode->getType(),
            $barcode->getCode(),
        );

        $data = $this->openAiWebSearch->completeWithWebSearchJson(
            $system,
            $user,
            self::RESPONSE_JSON_SCHEMA,
            self::JSON_SCHEMA_NAME,
        );

        $name = isset($data['name']) && \is_string($data['name']) ? trim($data['name']) : '';
        if ('' === $name) {
            throw new OpenAiResponsesWebSearchException('Lookup did not return a usable product name.');
        }

        $volAmount = $this->nullableStringField($data, 'volume_amount');
        $volUnitRaw = $this->nullableStringField($data, 'volume_unit');
        $volUnit = $this->normalizeVolumeUnit($volUnitRaw);

        $wAmount = $this->nullableStringField($data, 'weight_amount');
        $wUnitRaw = $this->nullableStringField($data, 'weight_unit');
        $wUnit = $this->normalizeWeightUnit($wUnitRaw);

        $abv = null;
        if (\array_key_exists('alcohol_percent', $data)) {
            $rawAbv = $data['alcohol_percent'];
            if (\is_int($rawAbv) || \is_float($rawAbv)) {
                $abv = (float) $rawAbv;
            }
        }

        if (null !== $volAmount xor null !== $volUnit) {
            $volAmount = null;
            $volUnit = null;
        }
        if (null !== $wAmount xor null !== $wUnit) {
            $wAmount = null;
            $wUnit = null;
        }

        $picnicId = $this->normalizePicnicProductId($this->nullableStringField($data, 'picnic_product_id'));
        $imageUrl = $this->normalizeProductImageUrl($this->nullableStringField($data, 'product_image_url'));

        return new BarcodeCatalogLookupDraft(
            self::PROVIDER_ID,
            $name,
            $volAmount,
            $volUnit,
            $wAmount,
            $wUnit,
            $abv,
            $barcode,
            new BarcodeCatalogLookupDraftExtras($picnicId, $imageUrl),
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    private function nullableStringField(array $data, string $key): ?string
    {
        if (!\array_key_exists($key, $data)) {
            return null;
        }
        $rawValue = $data[$key];
        if (null === $rawValue) {
            return null;
        }
        if (!\is_string($rawValue)) {
            return null;
        }
        $trimmed = trim($rawValue);

        return '' === $trimmed ? null : $trimmed;
    }

    private function normalizeVolumeUnit(?string $raw): ?string
    {
        if (null === $raw) {
            return null;
        }
        try {
            return VolumeUnit::from($raw)->value;
        } catch (ValueError) {
            return null;
        }
    }

    private function normalizeWeightUnit(?string $raw): ?string
    {
        if (null === $raw) {
            return null;
        }
        try {
            return WeightUnit::from($raw)->value;
        } catch (ValueError) {
            return null;
        }
    }

    private function normalizePicnicProductId(?string $raw): ?string
    {
        if (null === $raw) {
            return null;
        }
        $trimmed = trim($raw);
        if ('' === $trimmed) {
            return null;
        }
        if (\strlen($trimmed) > 200) {
            return null;
        }

        return $trimmed;
    }

    private function normalizeProductImageUrl(?string $raw): ?string
    {
        if (null === $raw) {
            return null;
        }
        $trimmedUrl = trim($raw);
        if ('' === $trimmedUrl) {
            return null;
        }
        if (\strlen($trimmedUrl) > 2048) {
            return null;
        }
        $lower = strtolower($trimmedUrl);
        if (!str_starts_with($lower, 'https://') && !str_starts_with($lower, 'http://')) {
            return null;
        }
        if (false === filter_var($trimmedUrl, \FILTER_VALIDATE_URL)) {
            return null;
        }
        $host = parse_url($trimmedUrl, \PHP_URL_HOST);
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
        $path = parse_url($trimmedUrl, \PHP_URL_PATH);
        if (!\is_string($path) || '' === $path || '/' === $path) {
            return null;
        }
        $pathLower = strtolower($path);
        if (1 === preg_match('/\.(html?|php|aspx)(\?|$)/', $pathLower)) {
            return null;
        }

        return $trimmedUrl;
    }
}

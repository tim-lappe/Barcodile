<?php

declare(strict_types=1);

namespace App\Catalog\Infrastructure\BarcodeLookup;

use App\AI\Domain\Exception\OpenAiResponsesWebSearchException;
use App\AI\Domain\Port\OpenAiResponsesWebSearchPort;
use App\Catalog\Domain\BarcodeLookup\BarcodeCatalogLookupDraft;
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
        ],
        'required' => [
            'name',
            'volume_amount',
            'volume_unit',
            'weight_amount',
            'weight_unit',
            'alcohol_percent',
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
TXT;

        $user = \sprintf(
            "Barcode type: %s\nBarcode code: %s\nReturn only the JSON object matching the schema (no markdown).",
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

        return new BarcodeCatalogLookupDraft(
            self::PROVIDER_ID,
            $name,
            $volAmount,
            $volUnit,
            $wAmount,
            $wUnit,
            $abv,
            $barcode->getCode(),
            $barcode->getType(),
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
        $v = $data[$key];
        if (null === $v) {
            return null;
        }
        if (!\is_string($v)) {
            return null;
        }
        $t = trim($v);

        return '' === $t ? null : $t;
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
}

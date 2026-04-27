<?php

declare(strict_types=1);

namespace App\Picnic\Infrastructure\Domains\Catalog;

final class CatalogProductDetailsRows
{
    /**
     * @return array{id: mixed, label: mixed}|null
     */
    public static function buildPromotion(mixed $page): ?array
    {
        $promoData = CatalogJsonPathQuery::query($page, '$..promotion_id');
        $promoLabels = CatalogJsonPathQuery::query($page, '$..promotion_label');
        if ([] === $promoData || [] === $promoLabels) {
            return null;
        }

        return [
            'id' => $promoData[0],
            'label' => $promoLabels[0],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function buildBundles(mixed $body): array
    {
        $bundles = [];
        $bundleContainer = CatalogBundleTreeWalker::findBundleContainer($body);
        if (null === $bundleContainer) {
            return $bundles;
        }
        $bundleItemNodes = [];
        CatalogBundleTreeWalker::walkBundleItems($bundleContainer, $bundleItemNodes);
        foreach ($bundleItemNodes as $index => $bundleNode) {
            $row = self::mapBundleNode($bundleNode, $index);
            if (null !== $row) {
                $bundles[] = $row;
            }
        }

        return $bundles;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function buildSimilarProducts(mixed $body): array
    {
        $altContainer = CatalogJsonTreeSearch::findById($body, 'alternatives-container');
        $altSellingUnits = null !== $altContainer
            ? CatalogJsonPathQuery::query($altContainer, '$..sellingUnit')
            : [];
        $similarProducts = [];
        foreach ($altSellingUnits as $unit) {
            $row = self::mapSimilarProductRow($unit);
            if (null !== $row) {
                $similarProducts[] = $row;
            }
        }

        return $similarProducts;
    }

    /**
     * @return list<array{title: string, content: string}>
     */
    public static function buildInfoSections(mixed $body): array
    {
        $items = self::accordionItemsList($body);
        if (null === $items) {
            return [];
        }
        $infoSections = [];
        foreach ($items as $item) {
            $section = self::mapAccordionItem($item);
            if (null !== $section) {
                $infoSections[] = $section;
            }
        }

        return $infoSections;
    }

    /**
     * @return list<mixed>|null
     */
    private static function accordionItemsList(mixed $body): ?array
    {
        $accordionBlock = CatalogJsonTreeSearch::findById($body, 'accordion-list');
        if (null === $accordionBlock) {
            return null;
        }
        $itemsArrays = CatalogJsonPathQuery::query($accordionBlock, '$..items');
        $items = $itemsArrays[0] ?? null;
        if (!\is_array($items) || !array_is_list($items)) {
            return null;
        }

        return $items;
    }

    /**
     * @return array{title: string, content: string}|null
     */
    private static function mapAccordionItem(mixed $item): ?array
    {
        if (!\is_array($item)) {
            return null;
        }
        $headerTexts = array_map(
            CatalogTextFormatting::stripColorMarkup(...),
            array_map(
                CatalogTextFormatting::stripMarkdownFormatting(...),
                CatalogTextFormatting::extractMarkdowns($item['header'] ?? null),
            ),
        );
        $bodyTexts = array_map(
            CatalogTextFormatting::stripColorMarkup(...),
            CatalogTextFormatting::extractMarkdowns($item['body'] ?? null),
        );

        return [
            'title' => $headerTexts[0] ?? '',
            'content' => implode("\n", $bodyTexts),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function mapSimilarProductRow(mixed $unit): ?array
    {
        if (!\is_array($unit)) {
            return null;
        }
        if (!\array_key_exists('display_price', $unit)) {
            return null;
        }
        $row = self::similarProductBaseRow(CatalogArrayKeyFilter::stringKeysOnly($unit));
        if (\array_key_exists('deposit', $unit)) {
            $row['deposit'] = $unit['deposit'];
        }

        return $row;
    }

    /**
     * @param array<string, mixed> $unit
     *
     * @return array<string, mixed>
     */
    private static function similarProductBaseRow(array $unit): array
    {
        return [
            'id' => $unit['id'] ?? '',
            'name' => $unit['name'] ?? '',
            'imageId' => $unit['image_id'] ?? '',
            'displayPrice' => self::similarDisplayPrice($unit),
            'unitQuantity' => $unit['unit_quantity'] ?? '',
            'maxCount' => self::similarMaxCount($unit),
        ];
    }

    /**
     * @param array<string, mixed> $unit
     */
    private static function similarDisplayPrice(array $unit): int
    {
        if (!isset($unit['display_price']) || !is_numeric($unit['display_price'])) {
            return 0;
        }

        return (int) $unit['display_price'];
    }

    /**
     * @param array<string, mixed> $unit
     */
    private static function similarMaxCount(array $unit): int
    {
        if (!isset($unit['max_count']) || !is_numeric($unit['max_count'])) {
            return 0;
        }

        return (int) $unit['max_count'];
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function mapBundleNode(mixed $bundleNode, int $index): ?array
    {
        $sellingUnits = CatalogJsonPathQuery::query($bundleNode, '$..sellingUnit');
        $firstUnit = $sellingUnits[0] ?? null;
        if (!\is_array($firstUnit)) {
            return null;
        }
        $numericPrices = self::numericPricesFromNode($bundleNode);

        return self::bundleRowFromUnit(CatalogArrayKeyFilter::stringKeysOnly($firstUnit), $index, $numericPrices);
    }

    /**
     * @return list<int|float>
     */
    private static function numericPricesFromNode(mixed $bundleNode): array
    {
        return array_values(array_filter(
            CatalogJsonPathQuery::query($bundleNode, '$..price'),
            static fn (mixed $price): bool => \is_int($price) || \is_float($price),
        ));
    }

    /**
     * @param array<string, mixed> $firstUnit
     * @param list<int|float>      $numericPrices
     *
     * @return array<string, mixed>
     */
    private static function bundleRowFromUnit(array $firstUnit, int $index, array $numericPrices): array
    {
        return [
            'id' => $firstUnit['id'] ?? '',
            'quantity' => $index + 1,
            'pricePerUnit' => isset($numericPrices[0]) ? (int) $numericPrices[0] : 0,
            'imageId' => self::bundleImageId($firstUnit),
            'maxCount' => self::bundleMaxCount($firstUnit),
        ];
    }

    /**
     * @param array<string, mixed> $firstUnit
     */
    private static function bundleImageId(array $firstUnit): string
    {
        if (!\is_string($firstUnit['image_id'] ?? null)) {
            return '';
        }

        return $firstUnit['image_id'];
    }

    /**
     * @param array<string, mixed> $firstUnit
     */
    private static function bundleMaxCount(array $firstUnit): int
    {
        if (!isset($firstUnit['max_count']) || !is_numeric($firstUnit['max_count'])) {
            return 0;
        }

        return (int) $firstUnit['max_count'];
    }
}

<?php

declare(strict_types=1);

namespace App\Infrastructure\Picnic\Domains\Catalog;

final class CatalogProductDetailsExtractor
{
    /**
     * @return array<string, mixed>
     */
    public static function extract(string $productId, mixed $page): array
    {
        $body = self::readPageBody($page);
        $mainContainer = CatalogJsonTreeSearch::findById($body, 'product-details-page-root-main-container');
        $mainTexts = self::mainTextsFromContainer($mainContainer);
        $mainUnit = self::resolveMainSellingUnit($page, $productId);
        $displayPrice = CatalogProductDetailsPricing::resolveDisplayPrice($body, $productId, $mainContainer, $mainUnit);

        return self::assembleResult($productId, $mainTexts, $mainUnit, $displayPrice, $body, $page);
    }

    /**
     * @return array{0: string, 1: string, 2: string, 3: string|null}
     */
    private static function mainTextsFromContainer(mixed $mainContainer): array
    {
        $stripped = array_map(
            CatalogTextFormatting::stripColorMarkup(...),
            CatalogTextFormatting::extractMarkdowns($mainContainer),
        );
        $name = $stripped[0] ?? '';
        $brand = $stripped[1] ?? '';
        $unitQuantity = $stripped[2] ?? '';
        $unitPrice = $stripped[3] ?? null;
        if ('' === $unitPrice) {
            $unitPrice = null;
        }

        return [$name, $brand, $unitQuantity, $unitPrice];
    }

    /**
     * @param array{0: string, 1: string, 2: string, 3: string|null} $mainTexts
     * @param array<string, mixed>                                   $mainUnit
     *
     * @return array<string, mixed>
     */
    private static function assembleResult(
        string $productId,
        array $mainTexts,
        array $mainUnit,
        int $displayPrice,
        mixed $body,
        mixed $page,
    ): array {
        [$name, $brand, $unitQuantity, $unitPrice] = $mainTexts;

        return [
            'id' => $productId,
            'name' => $name,
            'brand' => $brand,
            'unitQuantity' => $unitQuantity,
            'unitPrice' => $unitPrice,
            'displayPrice' => $displayPrice,
            'maxCount' => self::resolveMaxCount($mainUnit),
            'imageIds' => self::collectImageIds($body, $mainUnit),
            'description' => self::buildDescription($body),
            'highlights' => self::buildHighlights($body),
            'allergens' => self::buildAllergens($body),
            'infoSections' => CatalogProductDetailsRows::buildInfoSections($body),
            'promotion' => CatalogProductDetailsRows::buildPromotion($page),
            'bundles' => CatalogProductDetailsRows::buildBundles($body),
            'similarProducts' => CatalogProductDetailsRows::buildSimilarProducts($body),
        ];
    }

    private static function readPageBody(mixed $page): mixed
    {
        $fromArray = self::bodyFromArrayPage($page);
        if (null !== $fromArray) {
            return $fromArray;
        }

        return self::bodyFromObjectPage($page);
    }

    private static function bodyFromArrayPage(mixed $page): mixed
    {
        if (!\is_array($page) || !isset($page['body'])) {
            return null;
        }

        return $page['body'];
    }

    private static function bodyFromObjectPage(mixed $page): mixed
    {
        if (!\is_object($page) || !isset($page->body)) {
            return null;
        }

        return $page->body;
    }

    /**
     * @return array<string, mixed>
     */
    private static function resolveMainSellingUnit(mixed $page, string $productId): array
    {
        $allSellingUnits = CatalogJsonPathQuery::query($page, '$..sellingUnit');
        foreach ($allSellingUnits as $unit) {
            $match = self::matchingSellingUnit($unit, $productId);
            if (null !== $match) {
                return $match;
            }
        }

        return [];
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function matchingSellingUnit(mixed $unit, string $productId): ?array
    {
        if (!\is_array($unit)) {
            return null;
        }
        if (($unit['id'] ?? null) !== $productId) {
            return null;
        }
        if (!\array_key_exists('max_count', $unit)) {
            return null;
        }

        return CatalogArrayKeyFilter::stringKeysOnly($unit);
    }

    /**
     * @param array<string, mixed> $mainUnit
     */
    private static function resolveMaxCount(array $mainUnit): int
    {
        if (isset($mainUnit['max_count']) && is_numeric($mainUnit['max_count'])) {
            return (int) $mainUnit['max_count'];
        }

        return 0;
    }

    /**
     * @param array<string, mixed> $mainUnit
     *
     * @return list<string>
     */
    private static function collectImageIds(mixed $body, array $mainUnit): array
    {
        $gallery = CatalogJsonTreeSearch::findById($body, 'product-page-image-gallery-main-image-container');
        if (null !== $gallery) {
            return self::imageIdsFromGallery($gallery);
        }
        if (isset($mainUnit['image_id']) && \is_string($mainUnit['image_id'])) {
            return [$mainUnit['image_id']];
        }

        return [];
    }

    /**
     * @return list<string>
     */
    private static function imageIdsFromGallery(mixed $gallery): array
    {
        $ids = CatalogJsonPathQuery::query($gallery, '$..source.id');
        $imageIds = [];
        foreach ($ids as $imageId) {
            if (\is_string($imageId)) {
                $imageIds[$imageId] = true;
            }
        }

        return array_keys($imageIds);
    }

    private static function buildDescription(mixed $body): ?string
    {
        $descBlock = CatalogJsonTreeSearch::findById($body, 'description');
        $descMarkdowns = CatalogTextFormatting::extractMarkdowns($descBlock);

        return [] !== $descMarkdowns ? implode("\n", $descMarkdowns) : null;
    }

    /**
     * @return list<string>
     */
    private static function buildHighlights(mixed $body): array
    {
        $highlightsBlock = CatalogJsonTreeSearch::findById($body, 'product-page-highlights');

        return array_map(
            static fn (string $line): string => CatalogTextFormatting::stripMarkdownFormatting(
                CatalogTextFormatting::stripColorMarkup($line),
            ),
            CatalogTextFormatting::extractMarkdowns($highlightsBlock),
        );
    }

    /**
     * @return list<string>
     */
    private static function buildAllergens(mixed $body): array
    {
        $allergiesBlock = CatalogJsonTreeSearch::findById($body, 'product-page-allergies');
        $allergenTexts = array_map(
            CatalogTextFormatting::stripColorMarkup(...),
            CatalogTextFormatting::extractMarkdowns($allergiesBlock),
        );

        return array_values(array_filter(
            $allergenTexts,
            static fn (string $line): bool => !preg_match('/^bevat(\s+mogelijk)?$/i', trim($line)),
        ));
    }
}

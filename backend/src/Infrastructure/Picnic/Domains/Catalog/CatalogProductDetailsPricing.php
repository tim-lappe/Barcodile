<?php

declare(strict_types=1);

namespace App\Infrastructure\Picnic\Domains\Catalog;

final class CatalogProductDetailsPricing
{
    /**
     * @param array<string, mixed> $mainUnit
     */
    public static function resolveDisplayPrice(mixed $body, string $productId, mixed $mainContainer, array $mainUnit): int
    {
        $displayPrice = self::displayPriceFromMainUnit($mainUnit);
        if ($displayPrice > 0) {
            return $displayPrice;
        }
        $fromBundle = self::displayPriceFromBundleNode($body, $productId);
        if ($fromBundle > 0) {
            return $fromBundle;
        }

        return self::displayPriceFromMainContainer($mainContainer);
    }

    /**
     * @param array<string, mixed> $mainUnit
     */
    private static function displayPriceFromMainUnit(array $mainUnit): int
    {
        if (isset($mainUnit['display_price']) && is_numeric($mainUnit['display_price'])) {
            return (int) $mainUnit['display_price'];
        }

        return 0;
    }

    private static function displayPriceFromBundleNode(mixed $body, string $productId): int
    {
        $bundleNode = CatalogJsonTreeSearch::findById($body, $productId);
        if (null === $bundleNode) {
            return 0;
        }
        $prices = CatalogJsonPathQuery::query($bundleNode, '$..price');

        return self::firstNumericPriceAsInt($prices);
    }

    /**
     * @param list<mixed> $prices
     */
    private static function firstNumericPriceAsInt(array $prices): int
    {
        foreach ($prices as $price) {
            if (\is_int($price) || \is_float($price)) {
                return (int) $price;
            }
        }

        return 0;
    }

    private static function displayPriceFromMainContainer(mixed $mainContainer): int
    {
        if (null === $mainContainer) {
            return 0;
        }
        $priceComponents = array_filter(
            CatalogJsonPathQuery::query($mainContainer, '$..price'),
            static fn (mixed $price): bool => \is_int($price) || \is_float($price),
        );
        if ([] === $priceComponents) {
            return 0;
        }

        return (int) reset($priceComponents);
    }
}

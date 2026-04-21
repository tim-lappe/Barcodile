<?php

declare(strict_types=1);

namespace App\Infrastructure\Picnic\Domains\Catalog;

final class CatalogBundleTreeWalker
{
    public static function findBundleContainer(mixed $body): mixed
    {
        if (null === $body) {
            return null;
        }
        if (self::isBundleRootNode($body)) {
            return $body;
        }

        return self::scanChildrenForBundleContainer($body);
    }

    private static function isBundleRootNode(mixed $node): bool
    {
        if (\is_array($node)) {
            return self::bundleRootIdMatches($node['id'] ?? null);
        }
        if (\is_object($node)) {
            return self::bundleRootIdMatches($node->id ?? null);
        }

        return false;
    }

    private static function bundleRootIdMatches(mixed $rootId): bool
    {
        return \is_string($rootId) && str_starts_with($rootId, 'product-page-bundles-');
    }

    private static function scanChildrenForBundleContainer(mixed $node): mixed
    {
        if (\is_array($node)) {
            return self::scanArrayChildren($node);
        }
        if (\is_object($node)) {
            return self::scanObjectChildren($node);
        }

        return null;
    }

    /**
     * @param array<mixed> $node
     */
    private static function scanArrayChildren(array $node): mixed
    {
        foreach ($node as $value) {
            $found = self::findBundleContainer($value);
            if (null !== $found) {
                return $found;
            }
        }

        return null;
    }

    private static function scanObjectChildren(object $node): mixed
    {
        foreach (get_object_vars($node) as $value) {
            $found = self::findBundleContainer($value);
            if (null !== $found) {
                return $found;
            }
        }

        return null;
    }

    /**
     * @param list<mixed> $bundleItemNodes
     */
    public static function walkBundleItems(mixed $node, array &$bundleItemNodes): void
    {
        if (null === $node) {
            return;
        }
        if (\is_array($node)) {
            self::walkArrayNode($node, $bundleItemNodes);

            return;
        }
        if (\is_object($node)) {
            self::walkObjectNode($node, $bundleItemNodes);
        }
    }

    /**
     * @param array<mixed> $node
     * @param list<mixed>  $bundleItemNodes
     */
    private static function walkArrayNode(array $node, array &$bundleItemNodes): void
    {
        if (!array_is_list($node)) {
            self::walkAssociativeArrayNode($node, $bundleItemNodes);

            return;
        }
        foreach ($node as $item) {
            self::walkBundleItems($item, $bundleItemNodes);
        }
    }

    /**
     * @param array<mixed> $node
     * @param list<mixed>  $bundleItemNodes
     */
    private static function walkAssociativeArrayNode(array $node, array &$bundleItemNodes): void
    {
        if (self::isStateBoundaryBundleItemArray($node)) {
            $bundleItemNodes[] = $node;
        }
        foreach ($node as $value) {
            self::walkBundleItems($value, $bundleItemNodes);
        }
    }

    /**
     * @param list<mixed> $bundleItemNodes
     */
    private static function walkObjectNode(object $node, array &$bundleItemNodes): void
    {
        if (self::isStateBoundaryBundleItemObject($node)) {
            $bundleItemNodes[] = $node;
        }
        foreach (get_object_vars($node) as $value) {
            self::walkBundleItems($value, $bundleItemNodes);
        }
    }

    /**
     * @param array<mixed> $node
     */
    private static function isStateBoundaryBundleItemArray(array $node): bool
    {
        if (($node['type'] ?? null) !== 'STATE_BOUNDARY') {
            return false;
        }
        if (!isset($node['id']) || !\is_string($node['id'])) {
            return false;
        }

        return str_starts_with($node['id'], 's');
    }

    private static function isStateBoundaryBundleItemObject(object $node): bool
    {
        if (($node->type ?? null) !== 'STATE_BOUNDARY') {
            return false;
        }
        if (!isset($node->id) || !\is_string($node->id)) {
            return false;
        }

        return str_starts_with($node->id, 's');
    }
}

<?php

declare(strict_types=1);

namespace App\Infrastructure\Picnic\Domains\Catalog;

final class CatalogJsonTreeSearch
{
    public static function findById(mixed $node, string $targetId): mixed
    {
        if (null === $node) {
            return null;
        }
        $direct = self::directMatch($node, $targetId);
        if (null !== $direct) {
            return $direct;
        }

        return self::searchChildrenKeys($node, $targetId);
    }

    private static function directMatch(mixed $node, string $targetId): mixed
    {
        if (self::arrayMatchesId($node, $targetId)) {
            return $node;
        }
        if (self::objectMatchesId($node, $targetId)) {
            return $node;
        }

        return null;
    }

    private static function searchChildrenKeys(mixed $node, string $targetId): mixed
    {
        foreach (['child', 'children'] as $key) {
            $found = self::searchInKeyedChild($node, $key, $targetId);
            if (null !== $found) {
                return $found;
            }
        }

        return null;
    }

    private static function arrayMatchesId(mixed $node, string $targetId): bool
    {
        return \is_array($node) && ($node['id'] ?? null) === $targetId;
    }

    private static function objectMatchesId(mixed $node, string $targetId): bool
    {
        return \is_object($node) && property_exists($node, 'id') && $node->id === $targetId;
    }

    private static function searchInKeyedChild(mixed $node, string $key, string $targetId): mixed
    {
        $child = self::readChild($node, $key);
        if (null === $child) {
            return null;
        }

        return self::searchInChildValue($child, $targetId);
    }

    private static function readChild(mixed $node, string $key): mixed
    {
        $fromArray = self::readArrayChild($node, $key);
        if (null !== $fromArray) {
            return $fromArray;
        }

        return self::readObjectChild($node, $key);
    }

    private static function readArrayChild(mixed $node, string $key): mixed
    {
        if (!\is_array($node) || !\array_key_exists($key, $node)) {
            return null;
        }

        return $node[$key];
    }

    private static function readObjectChild(mixed $node, string $key): mixed
    {
        if (!\is_object($node) || !property_exists($node, $key)) {
            return null;
        }

        return $node->{$key};
    }

    private static function searchInChildValue(mixed $child, string $targetId): mixed
    {
        if (\is_array($child) && array_is_list($child)) {
            return self::searchInListChild($child, $targetId);
        }

        return self::searchInNonListChild($child, $targetId);
    }

    private static function searchInNonListChild(mixed $child, string $targetId): mixed
    {
        if (\is_array($child) || \is_object($child)) {
            return self::findById($child, $targetId);
        }

        return null;
    }

    /**
     * @param list<mixed> $listChild
     */
    private static function searchInListChild(array $listChild, string $targetId): mixed
    {
        foreach ($listChild as $element) {
            $result = self::findById($element, $targetId);
            if (null !== $result) {
                return $result;
            }
        }

        return null;
    }
}

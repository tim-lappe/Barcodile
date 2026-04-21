<?php

declare(strict_types=1);

namespace App\Infrastructure\Picnic\Domains\Catalog;

final class CatalogTextFormatting
{
    public static function stripColorMarkup(string $text): string
    {
        return trim(preg_replace('/#\([A-Za-z0-9#_]+\)/', '', $text) ?? '');
    }

    public static function stripMarkdownFormatting(string $text): string
    {
        return str_replace(['**', '__'], '', $text);
    }

    /**
     * @return list<string>
     */
    public static function extractMarkdowns(mixed $node): array
    {
        if (null === $node) {
            return [];
        }
        $all = CatalogJsonPathQuery::query($node, '$..markdown');
        $strings = [];
        foreach ($all as $markdown) {
            if (\is_string($markdown)) {
                $strings[] = $markdown;
            }
        }

        return $strings;
    }
}

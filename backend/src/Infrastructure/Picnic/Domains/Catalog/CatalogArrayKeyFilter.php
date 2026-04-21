<?php

declare(strict_types=1);

namespace App\Infrastructure\Picnic\Domains\Catalog;

final class CatalogArrayKeyFilter
{
    /**
     * @param array<mixed> $array
     *
     * @return array<string, mixed>
     */
    public static function stringKeysOnly(array $array): array
    {
        $result = [];
        foreach ($array as $key => $value) {
            if (\is_string($key)) {
                $result[$key] = $value;
            }
        }

        return $result;
    }
}

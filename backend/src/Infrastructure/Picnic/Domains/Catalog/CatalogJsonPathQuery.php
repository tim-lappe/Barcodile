<?php

declare(strict_types=1);

namespace App\Infrastructure\Picnic\Domains\Catalog;

use JsonPath\JsonObject;

final class CatalogJsonPathQuery
{
    /**
     * @return list<mixed>
     */
    public static function query(mixed $json, string $path): array
    {
        $obj = new JsonObject($json);
        $value = $obj->get($path);
        if (null === $value) {
            return [];
        }
        if (!\is_array($value)) {
            return [$value];
        }

        return array_values($value);
    }
}

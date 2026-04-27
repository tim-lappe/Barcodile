<?php

declare(strict_types=1);

namespace App\SharedKernel\Application;

use InvalidArgumentException;
use JsonException;

final class JsonBody
{
    /**
     * @return array<string, mixed>
     */
    public static function decodeObject(string $json): array
    {
        if ('' === trim($json)) {
            return [];
        }
        try {
            $raw = json_decode($json, true, 512, \JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidArgumentException('Invalid JSON.', 0, $exception);
        }
        if (!\is_array($raw)) {
            throw new InvalidArgumentException('Expected a JSON object.');
        }

        /** @var array<string, mixed> $out */
        $out = $raw;

        return $out;
    }
}

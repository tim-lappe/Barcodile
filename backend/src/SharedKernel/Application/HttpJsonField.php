<?php

declare(strict_types=1);

namespace App\SharedKernel\Application;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class HttpJsonField
{
    /**
     * @param array<mixed> $body
     */
    public static function requireString(array $body, string $key): string
    {
        if (!\array_key_exists($key, $body) || !\is_string($body[$key])) {
            throw new BadRequestHttpException('Field '.$key.' must be a string.');
        }

        return $body[$key];
    }

    /**
     * @param array<mixed> $body
     */
    public static function optionalStringOrNull(array $body, string $key): ?string
    {
        if (!\array_key_exists($key, $body)) {
            return null;
        }
        $rawValue = $body[$key];
        if (null === $rawValue) {
            return null;
        }
        if (!\is_string($rawValue)) {
            throw new BadRequestHttpException('Field '.$key.' must be a string or null.');
        }

        return $rawValue;
    }

    /**
     * @param array<mixed> $body
     */
    public static function requireInt(array $body, string $key): int
    {
        if (!\array_key_exists($key, $body)) {
            throw new BadRequestHttpException('Field '.$key.' is required.');
        }

        return self::parseIntValue($body[$key], $key);
    }

    private static function parseIntValue(mixed $rawValue, string $key): int
    {
        if (\is_int($rawValue)) {
            return $rawValue;
        }
        if (\is_string($rawValue) && is_numeric($rawValue)) {
            return (int) $rawValue;
        }
        throw new BadRequestHttpException('Field '.$key.' must be an integer.');
    }
}

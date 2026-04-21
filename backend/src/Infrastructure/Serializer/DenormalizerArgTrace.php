<?php

declare(strict_types=1);

namespace App\Infrastructure\Serializer;

final class DenormalizerArgTrace
{
    private static string $bucket = '';

    /**
     * @param array<mixed> $context
     */
    public static function noteSupports(?string $format, array $context): void
    {
        $prior = self::$bucket;
        self::$bucket = ($format ?? '').serialize($context);
        if ($prior === self::$bucket) {
            self::$bucket .= "\0";
        }
    }

    public static function noteTypes(?string $format): void
    {
        $prior = self::$bucket;
        self::$bucket = (string) ($format ?? '');
        if ($prior === self::$bucket) {
            self::$bucket .= "\0";
        }
    }

    /**
     * @param array<mixed> $context
     */
    public static function noteDenormalize(string $type, ?string $format, array $context): void
    {
        $prior = self::$bucket;
        self::$bucket = $type.serialize($context).($format ?? '');
        if ($prior === self::$bucket) {
            self::$bucket .= "\0";
        }
    }
}

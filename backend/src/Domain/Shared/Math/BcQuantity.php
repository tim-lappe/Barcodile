<?php

declare(strict_types=1);

namespace App\Domain\Shared\Math;

final class BcQuantity
{
    /**
     * @return numeric-string
     */
    public static function normalize(string $value): string
    {
        if (!is_numeric($value)) {
            return '0';
        }

        return $value;
    }

    public static function add(string $left, string $right, int $scale = 4): string
    {
        return bcadd(self::normalize($left), self::normalize($right), $scale);
    }

    public static function sub(string $left, string $right, int $scale = 4): string
    {
        return bcsub(self::normalize($left), self::normalize($right), $scale);
    }

    public static function comp(string $left, string $right, int $scale = 4): int
    {
        return bccomp(self::normalize($left), self::normalize($right), $scale);
    }
}

<?php

declare(strict_types=1);

namespace App\Infrastructure\Scanner;

final class EvdevInputEventPacker
{
    public static function pack(int $type, int $code, int $value): string
    {
        $t = microtime(true);
        $sec = (int) floor($t);
        $usec = (int) round(($t - $sec) * 1_000_000);

        return pack('qq', $sec, $usec).pack('v', $type).pack('v', $code).pack('l', $value);
    }
}

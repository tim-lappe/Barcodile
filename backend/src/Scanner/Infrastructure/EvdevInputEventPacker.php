<?php

declare(strict_types=1);

namespace App\Scanner\Infrastructure;

final class EvdevInputEventPacker
{
    public static function pack(int $type, int $code, int $value): string
    {
        $now = microtime(true);
        $sec = (int) floor($now);
        $usec = (int) round(($now - $sec) * 1_000_000);

        return pack('qq', $sec, $usec).pack('v', $type).pack('v', $code).pack('l', $value);
    }
}

<?php

declare(strict_types=1);

namespace App\Scanner\Infrastructure;

use App\Scanner\Domain\Entity\ScannerDevice;

final class EvdevConsoleLineFormatter
{
    public static function formatLine(ScannerDevice $device, int $type, int $code, int $value): string
    {
        return \sprintf(
            '[%s | %s] type=%d code=%d value=%d',
            $device->getName(),
            (string) $device->getId(),
            $type,
            $code,
            $value,
        );
    }
}

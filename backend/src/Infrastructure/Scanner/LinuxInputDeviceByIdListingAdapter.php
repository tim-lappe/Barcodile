<?php

declare(strict_types=1);

namespace App\Infrastructure\Scanner;

use App\Domain\Scanner\Port\InputDeviceListingPort;
use App\Domain\Scanner\ValueObject\ListedInputDevice;

final class LinuxInputDeviceByIdListingAdapter implements InputDeviceListingPort
{
    private const BY_ID_DIR = '/dev/input/by-id';

    public function listAvailableInputDevices(): array
    {
        if (!is_dir(self::BY_ID_DIR) || !is_readable(self::BY_ID_DIR)) {
            return [];
        }

        $paths = glob(self::BY_ID_DIR.'/*') ?: [];
        $out = [];
        foreach ($paths as $path) {
            if (!is_link($path)) {
                continue;
            }
            $resolved = realpath($path);
            if (false === $resolved) {
                continue;
            }
            $label = basename($path);
            $out[] = new ListedInputDevice($path, $label);
        }

        usort(
            $out,
            static fn (ListedInputDevice $a, ListedInputDevice $b): int => strcmp($a->label, $b->label),
        );

        return $out;
    }
}

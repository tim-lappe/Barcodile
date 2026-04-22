<?php

declare(strict_types=1);

namespace App\Infrastructure\Scanner;

use App\Domain\Scanner\Port\InputDeviceListingPort;
use App\Domain\Scanner\ValueObject\ListedInputDevice;

final class LinuxInputDeviceByIdListingAdapter implements InputDeviceListingPort
{
    private const BY_ID_DIR = '/dev/input/by-id';

    public function __construct(
        private readonly string $environment,
    ) {
    }

    public function listAvailableInputDevices(): array
    {
        $out = [];

        if ('dev' === $this->environment) {
            $out[] = new ListedInputDevice('test-device', 'Test Device');
        }

        if (!is_dir(self::BY_ID_DIR) || !is_readable(self::BY_ID_DIR)) {
            return $out;
        }

        $paths = glob(self::BY_ID_DIR.'/*') ?: [];
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

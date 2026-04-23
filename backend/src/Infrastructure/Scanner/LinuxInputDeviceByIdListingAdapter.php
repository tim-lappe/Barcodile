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
        $out = $this->syntheticDevs();
        if (!is_dir(self::BY_ID_DIR) || !is_readable(self::BY_ID_DIR)) {
            return $out;
        }

        return $this->sortedWithByIdDir($out);
    }

    /**
     * @param list<ListedInputDevice> $out
     *
     * @return list<ListedInputDevice>
     */
    private function sortedWithByIdDir(array $out): array
    {
        foreach ($this->linkPathsUnderById() as $path) {
            $out[] = new ListedInputDevice($path, basename($path));
        }

        usort(
            $out,
            static fn (ListedInputDevice $left, ListedInputDevice $right): int => strcmp(
                $left->label,
                $right->label,
            ),
        );

        return $out;
    }

    /**
     * @return list<string>
     *
     * @SuppressWarnings("PHPMD.CyclomaticComplexity")
     */
    private function linkPathsUnderById(): array
    {
        $paths = [];
        foreach (glob(self::BY_ID_DIR.'/*') ?: [] as $path) {
            if (is_link($path) && false !== realpath($path)) {
                $paths[] = $path;
            }
        }

        return $paths;
    }

    /**
     * @return list<ListedInputDevice>
     */
    private function syntheticDevs(): array
    {
        if ('dev' === $this->environment) {
            return [new ListedInputDevice('test-device', 'Test Device')];
        }

        return [];
    }
}

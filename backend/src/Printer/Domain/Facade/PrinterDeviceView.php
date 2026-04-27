<?php

declare(strict_types=1);

namespace App\Printer\Domain\Facade;

final readonly class PrinterDeviceView
{
    /**
     * @param array<string, mixed> $connection
     * @param array<string, mixed> $printSettings
     */
    public function __construct(
        public string $resourceId,
        public string $driverCode,
        public array $connection,
        public array $printSettings,
        public string $name,
    ) {
    }
}

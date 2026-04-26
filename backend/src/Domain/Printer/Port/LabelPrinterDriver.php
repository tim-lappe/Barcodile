<?php

declare(strict_types=1);

namespace App\Domain\Printer\Port;

use App\Domain\Printer\ValueObject\DiscoveredPrinterOption;

interface LabelPrinterDriver
{
    public function driverCode(): string;

    public function displayLabel(): string;

    /**
     * @return list<DiscoveredPrinterOption>
     */
    public function discover(): array;

    /**
     * @param array<string, mixed> $connection
     */
    public function assertValidConnection(array $connection): void;

    /**
     * @param array<string, mixed> $connection
     */
    public function printTestLabel(array $connection): void;
}

<?php

declare(strict_types=1);

namespace App\Domain\Printer\Port;

use App\Domain\Printer\ValueObject\DiscoveredPrinterOption;

interface LabelPrinterDriver
{
    public function driverCode(): string;

    public function displayLabel(): string;

    /**
     * @return array<string, mixed>
     */
    public function defaultPrintSettings(): array;

    /**
     * @return array<string, mixed>
     */
    public function printSettingOptions(): array;

    /**
     * @return list<DiscoveredPrinterOption>
     */
    public function discover(): array;

    /**
     * @param array<string, mixed> $connection
     */
    public function assertValidConnection(array $connection): void;

    /**
     * @param array<string, mixed> $printSettings
     */
    public function assertValidPrintSettings(array $printSettings): void;

    /**
     * @param array<string, mixed> $connection
     * @param array<string, mixed> $printSettings
     */
    public function printTestLabel(array $connection, array $printSettings): void;

    /**
     * @param array<string, mixed> $connection
     * @param array<string, mixed> $printSettings
     */
    public function printLabelImage(array $connection, array $printSettings, string $pngBytes): void;
}

<?php

declare(strict_types=1);

namespace App\Printer\Domain\Port;

use App\Printer\Domain\Dto\LabelPrinterConnection;
use App\Printer\Domain\Dto\LabelPrintSettingOptions;
use App\Printer\Domain\Dto\LabelPrintSettings;
use App\Printer\Domain\ValueObject\DiscoveredPrinterOption;
use App\Printer\Domain\ValueObject\PrinterDriverCode;
use App\Printer\Domain\ValueObject\PrinterDriverDisplayLabel;

interface LabelPrinterDriver
{
    public function driverCode(): PrinterDriverCode;

    public function displayLabel(): PrinterDriverDisplayLabel;

    public function defaultPrintSettings(): LabelPrintSettings;

    public function printSettingOptions(): LabelPrintSettingOptions;

    /**
     * @return list<DiscoveredPrinterOption>
     */
    public function discover(): array;

    /**
     * @param array<string, mixed> $connection
     */
    public function createConnection(array $connection): LabelPrinterConnection;

    /**
     * @param array<string, mixed> $printSettings
     */
    public function createPrintSettings(array $printSettings): LabelPrintSettings;

    public function printTestLabel(LabelPrinterConnection $connection, LabelPrintSettings $printSettings): void;

    public function printLabelImage(LabelPrinterConnection $connection, LabelPrintSettings $printSettings, string $pngBytes): void;
}

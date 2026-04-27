<?php

declare(strict_types=1);

namespace App\Domain\Printer\Port;

use App\Domain\Printer\Dto\LabelPrinterConnection;
use App\Domain\Printer\Dto\LabelPrintSettingOptions;
use App\Domain\Printer\Dto\LabelPrintSettings;
use App\Domain\Printer\ValueObject\DiscoveredPrinterOption;
use App\Domain\Printer\ValueObject\PrinterDriverCode;
use App\Domain\Printer\ValueObject\PrinterDriverDisplayLabel;

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

<?php

declare(strict_types=1);

namespace App\Infrastructure\Printer\Test;

use App\Domain\Printer\Dto\ColorModePrintSettingOption;
use App\Domain\Printer\Dto\LabelPrinterConnection;
use App\Domain\Printer\Dto\LabelPrintSettingOptions;
use App\Domain\Printer\Dto\LabelPrintSettings;
use App\Domain\Printer\Dto\LabelSizePrintSettingOption;
use App\Domain\Printer\Exception\LabelPrintJobFailedException;
use App\Domain\Printer\Port\LabelPrinterDriver;
use App\Domain\Printer\ValueObject\DiscoveredPrinterOption;
use App\Domain\Printer\ValueObject\PrinterDriverCode;
use App\Domain\Printer\ValueObject\PrinterDriverDisplayLabel;
use Psr\Log\LoggerInterface;

final readonly class TestLabelPrinterDriver implements LabelPrinterDriver
{
    private const DRIVER_CODE = 'test';

    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    public function driverCode(): PrinterDriverCode
    {
        return new PrinterDriverCode(self::DRIVER_CODE);
    }

    public function displayLabel(): PrinterDriverDisplayLabel
    {
        return new PrinterDriverDisplayLabel('Test printer (logger)');
    }

    public function defaultPrintSettings(): LabelPrintSettings
    {
        return TestLabelPrintSettings::defaults();
    }

    public function printSettingOptions(): LabelPrintSettingOptions
    {
        return new LabelPrintSettingOptions(
            [
                new LabelSizePrintSettingOption(TestLabelPrintSettings::LABEL_SIZE, 'Logger test label'),
            ],
            [
                new ColorModePrintSettingOption('black', 'Black only', false),
            ],
        );
    }

    public function discover(): array
    {
        return [
            new DiscoveredPrinterOption(
                TestLabelPrinterConnection::PRINTER_IDENTIFIER,
                'Logger test printer',
                TestLabelPrinterConnection::defaults(),
                $this->defaultPrintSettings(),
            ),
        ];
    }

    public function createConnection(array $connection): LabelPrinterConnection
    {
        return TestLabelPrinterConnection::fromArray($connection);
    }

    public function createPrintSettings(array $printSettings): LabelPrintSettings
    {
        return TestLabelPrintSettings::fromArray($printSettings);
    }

    public function printTestLabel(LabelPrinterConnection $connection, LabelPrintSettings $printSettings): void
    {
        $testConnection = $this->testConnection($connection);
        $testPrintSettings = $this->testPrintSettings($printSettings);
        $this->logger->info('Test printer received a test label print request.', [
            'driverCode' => self::DRIVER_CODE,
            'connection' => $testConnection->connectionData(),
            'printSettings' => $testPrintSettings->printSettingsData(),
        ]);
    }

    public function printLabelImage(
        LabelPrinterConnection $connection,
        LabelPrintSettings $printSettings,
        string $pngBytes,
    ): void {
        $testConnection = $this->testConnection($connection);
        $testPrintSettings = $this->testPrintSettings($printSettings);
        $this->logger->info('Test printer received a label image print request.', [
            'driverCode' => self::DRIVER_CODE,
            'connection' => $testConnection->connectionData(),
            'printSettings' => $testPrintSettings->printSettingsData(),
            'imageBytes' => \strlen($pngBytes),
        ]);
    }

    private function testConnection(LabelPrinterConnection $connection): TestLabelPrinterConnection
    {
        if (!$connection instanceof TestLabelPrinterConnection) {
            throw new LabelPrintJobFailedException('Test printer connection is required.');
        }

        return $connection;
    }

    private function testPrintSettings(LabelPrintSettings $printSettings): TestLabelPrintSettings
    {
        if (!$printSettings instanceof TestLabelPrintSettings) {
            throw new LabelPrintJobFailedException('Test print settings are required.');
        }

        return $printSettings;
    }
}

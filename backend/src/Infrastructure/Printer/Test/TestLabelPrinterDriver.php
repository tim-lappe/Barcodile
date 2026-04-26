<?php

declare(strict_types=1);

namespace App\Infrastructure\Printer\Test;

use App\Domain\Printer\Exception\LabelPrintJobFailedException;
use App\Domain\Printer\Port\LabelPrinterDriver;
use App\Domain\Printer\ValueObject\DiscoveredPrinterOption;
use Psr\Log\LoggerInterface;

final readonly class TestLabelPrinterDriver implements LabelPrinterDriver
{
    private const DRIVER_CODE = 'test';
    private const PRINTER_IDENTIFIER = 'test://logger';
    private const LABEL_SIZE = 'logger';

    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    public function driverCode(): string
    {
        return self::DRIVER_CODE;
    }

    public function displayLabel(): string
    {
        return 'Test printer (logger)';
    }

    public function defaultPrintSettings(): array
    {
        return ['labelSize' => self::LABEL_SIZE, 'red' => false];
    }

    public function printSettingOptions(): array
    {
        return [
            'labelSizes' => [
                ['value' => self::LABEL_SIZE, 'label' => 'Logger test label'],
            ],
            'colorModes' => [
                ['value' => 'black', 'label' => 'Black only', 'red' => false],
            ],
        ];
    }

    public function discover(): array
    {
        return [
            new DiscoveredPrinterOption(
                self::PRINTER_IDENTIFIER,
                'Logger test printer',
                ['printerIdentifier' => self::PRINTER_IDENTIFIER],
                $this->defaultPrintSettings(),
            ),
        ];
    }

    public function assertValidConnection(array $connection): void
    {
        if (($connection['printerIdentifier'] ?? null) !== self::PRINTER_IDENTIFIER) {
            throw new LabelPrintJobFailedException('Invalid test printer connection.');
        }
    }

    public function assertValidPrintSettings(array $printSettings): void
    {
        if (($printSettings['labelSize'] ?? null) !== self::LABEL_SIZE) {
            throw new LabelPrintJobFailedException('Invalid test printer label size.');
        }
        if (($printSettings['red'] ?? null) !== false) {
            throw new LabelPrintJobFailedException('Invalid test printer color mode.');
        }
    }

    public function printTestLabel(array $connection, array $printSettings): void
    {
        $this->assertValidConnection($connection);
        $this->assertValidPrintSettings($printSettings);
        $this->logger->info('Test printer received a test label print request.', [
            'driverCode' => self::DRIVER_CODE,
            'connection' => $connection,
            'printSettings' => $printSettings,
        ]);
    }
}

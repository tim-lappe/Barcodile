<?php

declare(strict_types=1);

namespace App\Domain\Printer\Facade;

use App\Domain\Printer\Dto\ColorModePrintSettingOption;
use App\Domain\Printer\Dto\LabelPrinterConnection;
use App\Domain\Printer\Dto\LabelPrintSettingOptions;
use App\Domain\Printer\Dto\LabelPrintSettings;
use App\Domain\Printer\Dto\LabelSizePrintSettingOption;
use App\Domain\Printer\Entity\PrinterDevice;
use App\Domain\Printer\Exception\LabelPrintJobFailedException;
use App\Domain\Printer\Repository\PrinterDeviceRepository;
use App\Domain\Shared\Id\PrinterDeviceId;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class PrinterDeviceFacade
{
    public function __construct(
        private PrinterDeviceRepository $deviceRepository,
        private LabelPrinterDriverRegistry $driverRegistry,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @return list<PrinterDeviceView>
     */
    public function listPrinterDevices(): array
    {
        return array_map(
            fn (PrinterDevice $device): PrinterDeviceView => $this->map($device),
            $this->deviceRepository->findAllOrderedByName(),
        );
    }

    /**
     * @return list<PrinterDriverView>
     */
    public function listPrinterDrivers(): array
    {
        $out = [];
        foreach ($this->driverRegistry->all() as $driver) {
            $out[] = new PrinterDriverView(
                $driver->driverCode()->value(),
                $driver->displayLabel()->value(),
                $driver->defaultPrintSettings()->printSettingsData(),
                $this->mapPrintSettingOptions($driver->printSettingOptions()),
            );
        }

        return $out;
    }

    /**
     * @return list<DiscoveredPrinterOptionView>
     */
    public function listDiscoveryOptions(string $driverCode): array
    {
        $driver = $this->driverRegistry->get($driverCode);
        $out = [];
        foreach ($driver->discover() as $option) {
            $out[] = new DiscoveredPrinterOptionView(
                $option->deviceIdentifier,
                $option->label,
                $this->mapSuggestedConnection($option->suggestedConnection),
                $this->mapSuggestedSettings($option->suggestedSettings),
            );
        }

        return $out;
    }

    /**
     * @param array<string, mixed> $connectionData
     * @param array<string, mixed> $printSettingsData
     */
    public function createPrinterDevice(
        string $driverCode,
        array $connectionData,
        array $printSettingsData,
        string $name,
    ): PrinterDeviceView {
        $driverCode = trim($driverCode);
        $driver = $this->driverRegistry->get($driverCode);
        $connection = $driver->createConnection($connectionData);
        $printSettings = $driver->createPrintSettings($printSettingsData);

        $device = new PrinterDevice();
        $device->changeDriverCode($driverCode);
        $device->changeConnection($connection->connectionData());
        $device->changePrintSettings($printSettings->printSettingsData());
        $device->changeName(trim($name));
        $this->deviceRepository->save($device);
        $this->logger->info('Printer device created.', [
            'printerDeviceId' => (string) $device->getId(),
            'driverCode' => $device->getDriverCode(),
            'connection' => $device->getConnection(),
            'printSettings' => $device->getPrintSettings(),
        ]);

        return $this->map($device);
    }

    public function deletePrinterDevice(string $printerDeviceId): void
    {
        $this->deviceRepository->remove($this->mustFind($printerDeviceId));
    }

    public function getPrinterDevice(string $printerDeviceId): PrinterDeviceView
    {
        return $this->map($this->mustFind($printerDeviceId));
    }

    public function printTestLabel(string $printerDeviceId): void
    {
        $device = $this->mustFind($printerDeviceId);
        $driver = $this->driverRegistry->get($device->getDriverCode());
        $this->logTestPrintRequest($device);

        try {
            $driver->printTestLabel(
                $driver->createConnection($device->getConnection()),
                $driver->createPrintSettings($device->getPrintSettings()),
            );
        } catch (LabelPrintJobFailedException $e) {
            $this->logTestPrintFailure($device, $e);
            throw new BadRequestHttpException($e->getMessage(), $e);
        }
        $this->logger->info('Printer test label finished.', [
            'printerDeviceId' => (string) $device->getId(),
            'driverCode' => $device->getDriverCode(),
        ]);
    }

    public function printLabelImage(string $printerDeviceId, string $pngBytes): void
    {
        $device = $this->mustFind($printerDeviceId);
        $driver = $this->driverRegistry->get($device->getDriverCode());
        $this->logLabelImagePrintRequest($device, $pngBytes);

        try {
            $driver->printLabelImage(
                $driver->createConnection($device->getConnection()),
                $driver->createPrintSettings($device->getPrintSettings()),
                $pngBytes,
            );
        } catch (LabelPrintJobFailedException $e) {
            $this->logLabelImagePrintFailure($device, $e);
            throw new BadRequestHttpException($e->getMessage(), $e);
        }
        $this->logger->info('Printer label image finished.', [
            'printerDeviceId' => (string) $device->getId(),
            'driverCode' => $device->getDriverCode(),
        ]);
    }

    private function mustFind(string $printerDeviceId): PrinterDevice
    {
        $device = $this->deviceRepository->find(PrinterDeviceId::fromString($printerDeviceId));
        if (!$device instanceof PrinterDevice) {
            throw new NotFoundHttpException('Printer device not found.');
        }

        return $device;
    }

    private function map(PrinterDevice $device): PrinterDeviceView
    {
        return new PrinterDeviceView(
            (string) $device->getId(),
            $device->getDriverCode(),
            $device->getConnection(),
            $device->getPrintSettings(),
            $device->getName(),
        );
    }

    /**
     * @return array{labelSizes: list<array{value: string, label: string}>, colorModes: list<array{value: string, label: string, red: bool}>}
     */
    private function mapPrintSettingOptions(LabelPrintSettingOptions $options): array
    {
        return [
            'labelSizes' => array_map(
                static fn (LabelSizePrintSettingOption $option): array => [
                    'value' => $option->value,
                    'label' => $option->label,
                ],
                $options->labelSizes,
            ),
            'colorModes' => array_map(
                static fn (ColorModePrintSettingOption $option): array => [
                    'value' => $option->value,
                    'label' => $option->label,
                    'red' => $option->red,
                ],
                $options->colorModes,
            ),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function mapSuggestedConnection(?LabelPrinterConnection $connection): array
    {
        if (null === $connection) {
            return [];
        }

        return $connection->connectionData();
    }

    /**
     * @return array<string, mixed>
     */
    private function mapSuggestedSettings(?LabelPrintSettings $settings): array
    {
        if (null === $settings) {
            return [];
        }

        return $settings->printSettingsData();
    }

    private function logTestPrintRequest(PrinterDevice $device): void
    {
        $this->logger->info('Printer test label requested.', [
            'printerDeviceId' => (string) $device->getId(),
            'driverCode' => $device->getDriverCode(),
            'connection' => $device->getConnection(),
            'printSettings' => $device->getPrintSettings(),
        ]);
    }

    private function logTestPrintFailure(
        PrinterDevice $device,
        LabelPrintJobFailedException $failure,
    ): void {
        $this->logger->error('Printer test label failed.', [
            'printerDeviceId' => (string) $device->getId(),
            'driverCode' => $device->getDriverCode(),
            'error' => $failure->getMessage(),
        ]);
    }

    private function logLabelImagePrintRequest(PrinterDevice $device, string $pngBytes): void
    {
        $this->logger->info('Printer label image requested.', [
            'printerDeviceId' => (string) $device->getId(),
            'driverCode' => $device->getDriverCode(),
            'connection' => $device->getConnection(),
            'printSettings' => $device->getPrintSettings(),
            'imageBytes' => \strlen($pngBytes),
        ]);
    }

    private function logLabelImagePrintFailure(
        PrinterDevice $device,
        LabelPrintJobFailedException $failure,
    ): void {
        $this->logger->error('Printer label image failed.', [
            'printerDeviceId' => (string) $device->getId(),
            'driverCode' => $device->getDriverCode(),
            'error' => $failure->getMessage(),
        ]);
    }
}

<?php

declare(strict_types=1);

namespace App\Printer\Application;

use App\Printer\Api\Dto\DiscoveredPrinterOptionResponse;
use App\Printer\Api\Dto\PostPrinterDeviceRequest;
use App\Printer\Api\Dto\PrinterDeviceResponse;
use App\Printer\Api\Dto\PrinterDriverListItemResponse;
use App\Printer\Domain\Dto\ColorModePrintSettingOption;
use App\Printer\Domain\Dto\LabelPrinterConnection;
use App\Printer\Domain\Dto\LabelPrintSettingOptions;
use App\Printer\Domain\Dto\LabelPrintSettings;
use App\Printer\Domain\Dto\LabelSizePrintSettingOption;
use App\Printer\Domain\Entity\PrinterDevice;
use App\Printer\Domain\Exception\LabelPrintJobFailedException;
use App\Printer\Domain\Repository\PrinterDeviceRepository;
use App\Printer\Domain\Service\LabelPrinterDriverRegistry;
use App\SharedKernel\Domain\Id\PrinterDeviceId;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class PrinterDeviceApplicationService
{
    public function __construct(
        private PrinterDeviceRepository $deviceRepository,
        private LabelPrinterDriverRegistry $driverRegistry,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @return list<PrinterDeviceResponse>
     */
    public function listPrinterDevices(): array
    {
        return array_map(
            fn (PrinterDevice $device): PrinterDeviceResponse => $this->mapDevice($this->map($device)),
            $this->deviceRepository->findAllOrderedByName(),
        );
    }

    /**
     * @return list<PrinterDriverListItemResponse>
     */
    public function listPrinterDrivers(): array
    {
        return array_map(
            static fn (PrinterDriverView $driver): PrinterDriverListItemResponse => new PrinterDriverListItemResponse(
                $driver->code,
                $driver->label,
                $driver->defaultPrintSettings,
                $driver->printSettingOptions,
            ),
            $this->printerDriverViews(),
        );
    }

    /**
     * @return list<DiscoveredPrinterOptionResponse>
     */
    public function listDiscoveryOptions(string $driverCode): array
    {
        return array_map(
            static fn (DiscoveredPrinterOptionView $option): DiscoveredPrinterOptionResponse => new DiscoveredPrinterOptionResponse(
                $option->deviceIdentifier,
                $option->label,
                $option->suggestedConnection,
                $option->suggestedSettings,
            ),
            $this->discoveryOptionViews($driverCode),
        );
    }

    public function createPrinterDevice(PostPrinterDeviceRequest $request): PrinterDeviceResponse
    {
        $driverCode = trim($request->driverCode);
        $driver = $this->driverRegistry->get($driverCode);
        $connection = $driver->createConnection($request->connection);
        $printSettings = $driver->createPrintSettings($request->printSettings);

        $device = new PrinterDevice();
        $device->changeDriverCode($driverCode);
        $device->changeConnection($connection->connectionData());
        $device->changePrintSettings($printSettings->printSettingsData());
        $device->changeName(trim($request->name));
        $this->deviceRepository->save($device);
        $this->logger->info('Printer device created.', [
            'printerDeviceId' => (string) $device->getId(),
            'driverCode' => $device->getDriverCode(),
            'connection' => $device->getConnection(),
            'printSettings' => $device->getPrintSettings(),
        ]);

        return $this->mapDevice($this->map($device));
    }

    public function deletePrinterDevice(string $printerDeviceId): void
    {
        $this->deviceRepository->remove($this->mustFind($printerDeviceId));
    }

    public function getPrinterDevice(string $printerDeviceId): PrinterDeviceResponse
    {
        return $this->mapDevice($this->map($this->mustFind($printerDeviceId)));
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

    private function mapDevice(PrinterDeviceView $device): PrinterDeviceResponse
    {
        return new PrinterDeviceResponse(
            $device->resourceId,
            $device->driverCode,
            $device->connection,
            $device->printSettings,
            $device->name,
        );
    }

    /**
     * @return list<PrinterDriverView>
     */
    private function printerDriverViews(): array
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
    private function discoveryOptionViews(string $driverCode): array
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

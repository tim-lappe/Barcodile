<?php

declare(strict_types=1);

namespace App\Application\Printer;

use App\Application\Printer\Dto\DiscoveredPrinterOptionResponse;
use App\Application\Printer\Dto\PostPrinterDeviceRequest;
use App\Application\Printer\Dto\PrinterDeviceResponse;
use App\Application\Printer\Dto\PrinterDriverListItemResponse;
use App\Domain\Printer\Facade\DiscoveredPrinterOptionView;
use App\Domain\Printer\Facade\PrinterDeviceFacade;
use App\Domain\Printer\Facade\PrinterDeviceView;
use App\Domain\Printer\Facade\PrinterDriverView;

final readonly class PrinterDeviceApplicationService
{
    public function __construct(
        private PrinterDeviceFacade $printerDevices,
    ) {
    }

    /**
     * @return list<PrinterDeviceResponse>
     */
    public function listPrinterDevices(): array
    {
        return array_map(fn (PrinterDeviceView $device): PrinterDeviceResponse => $this->mapDevice($device), $this->printerDevices->listPrinterDevices());
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
            $this->printerDevices->listPrinterDrivers(),
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
            $this->printerDevices->listDiscoveryOptions($driverCode),
        );
    }

    public function createPrinterDevice(PostPrinterDeviceRequest $request): PrinterDeviceResponse
    {
        return $this->mapDevice($this->printerDevices->createPrinterDevice(
            $request->driverCode,
            $request->connection,
            $request->printSettings,
            $request->name,
        ));
    }

    public function deletePrinterDevice(string $printerDeviceId): void
    {
        $this->printerDevices->deletePrinterDevice($printerDeviceId);
    }

    public function getPrinterDevice(string $printerDeviceId): PrinterDeviceResponse
    {
        return $this->mapDevice($this->printerDevices->getPrinterDevice($printerDeviceId));
    }

    public function printTestLabel(string $printerDeviceId): void
    {
        $this->printerDevices->printTestLabel($printerDeviceId);
    }

    public function printLabelImage(string $printerDeviceId, string $pngBytes): void
    {
        $this->printerDevices->printLabelImage($printerDeviceId, $pngBytes);
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
}

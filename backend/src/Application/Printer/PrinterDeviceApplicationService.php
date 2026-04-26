<?php

declare(strict_types=1);

namespace App\Application\Printer;

use App\Application\Printer\Dto\DiscoveredPrinterOptionResponse;
use App\Application\Printer\Dto\PostPrinterDeviceRequest;
use App\Application\Printer\Dto\PrinterDeviceResponse;
use App\Application\Printer\Dto\PrinterDriverListItemResponse;
use App\Domain\Printer\Entity\PrinterDevice;
use App\Domain\Printer\Exception\LabelPrintJobFailedException;
use App\Domain\Printer\Repository\PrinterDeviceRepository;
use App\Domain\Shared\Id\PrinterDeviceId;
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
        $out = [];
        foreach ($this->deviceRepository->findAllOrderedByName() as $device) {
            $out[] = $this->map($device);
        }

        return $out;
    }

    /**
     * @return list<PrinterDriverListItemResponse>
     */
    public function listPrinterDrivers(): array
    {
        $out = [];
        foreach ($this->driverRegistry->all() as $driver) {
            $out[] = new PrinterDriverListItemResponse(
                $driver->driverCode(),
                $driver->displayLabel(),
                $driver->defaultPrintSettings(),
                $driver->printSettingOptions(),
            );
        }

        return $out;
    }

    /**
     * @return list<DiscoveredPrinterOptionResponse>
     */
    public function listDiscoveryOptions(string $driverCode): array
    {
        $driver = $this->driverRegistry->get($driverCode);
        $out = [];
        foreach ($driver->discover() as $option) {
            $out[] = new DiscoveredPrinterOptionResponse(
                $option->deviceIdentifier,
                $option->label,
                $option->suggestedConnection,
                $option->suggestedSettings,
            );
        }

        return $out;
    }

    public function createPrinterDevice(PostPrinterDeviceRequest $request): PrinterDeviceResponse
    {
        $driver = $this->driverRegistry->get(trim($request->driverCode));
        $driver->assertValidConnection($request->connection);
        $driver->assertValidPrintSettings($request->printSettings);

        $device = new PrinterDevice();
        $device->changeDriverCode(trim($request->driverCode));
        $device->changeConnection($request->connection);
        $device->changePrintSettings($request->printSettings);
        $device->changeName(trim($request->name));
        $this->deviceRepository->save($device);
        $this->logger->info('Printer device created.', [
            'printerDeviceId' => (string) $device->getId(),
            'driverCode' => $device->getDriverCode(),
            'connection' => $device->getConnection(),
            'printSettings' => $device->getPrintSettings(),
        ]);

        return $this->map($device);
    }

    public function deletePrinterDevice(PrinterDeviceId $printerDeviceId): void
    {
        $device = $this->deviceRepository->find($printerDeviceId);
        if (!$device instanceof PrinterDevice) {
            throw new NotFoundHttpException('Printer device not found.');
        }
        $this->deviceRepository->remove($device);
    }

    public function getPrinterDevice(PrinterDeviceId $printerDeviceId): PrinterDeviceResponse
    {
        $device = $this->deviceRepository->find($printerDeviceId);
        if (!$device instanceof PrinterDevice) {
            throw new NotFoundHttpException('Printer device not found.');
        }

        return $this->map($device);
    }

    public function printTestLabel(PrinterDeviceId $printerDeviceId): void
    {
        $device = $this->deviceRepository->find($printerDeviceId);
        if (!$device instanceof PrinterDevice) {
            throw new NotFoundHttpException('Printer device not found.');
        }

        $driver = $this->driverRegistry->get($device->getDriverCode());
        $this->logTestPrintRequest($device);

        try {
            $driver->printTestLabel($device->getConnection(), $device->getPrintSettings());
        } catch (LabelPrintJobFailedException $e) {
            $this->logTestPrintFailure($device, $e);
            throw new BadRequestHttpException($e->getMessage(), $e);
        }
        $this->logger->info('Printer test label finished.', [
            'printerDeviceId' => (string) $device->getId(),
            'driverCode' => $device->getDriverCode(),
        ]);
    }

    private function map(PrinterDevice $device): PrinterDeviceResponse
    {
        return new PrinterDeviceResponse(
            (string) $device->getId(),
            $device->getDriverCode(),
            $device->getConnection(),
            $device->getPrintSettings(),
            $device->getName(),
        );
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
}

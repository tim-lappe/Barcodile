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
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class PrinterDeviceApplicationService
{
    public function __construct(
        private PrinterDeviceRepository $deviceRepository,
        private LabelPrinterDriverRegistry $driverRegistry,
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
            $out[] = new PrinterDriverListItemResponse($driver->driverCode(), $driver->displayLabel());
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
            );
        }

        return $out;
    }

    public function createPrinterDevice(PostPrinterDeviceRequest $request): PrinterDeviceResponse
    {
        $driver = $this->driverRegistry->get(trim($request->driverCode));
        $driver->assertValidConnection($request->connection);

        $device = new PrinterDevice();
        $device->changeDriverCode(trim($request->driverCode));
        $device->changeConnection($request->connection);
        $device->changeName(trim($request->name));
        $this->deviceRepository->save($device);

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

        try {
            $driver->printTestLabel($device->getConnection());
        } catch (LabelPrintJobFailedException $e) {
            throw new BadRequestHttpException($e->getMessage(), $e);
        }
    }

    private function map(PrinterDevice $device): PrinterDeviceResponse
    {
        return new PrinterDeviceResponse(
            (string) $device->getId(),
            $device->getDriverCode(),
            $device->getConnection(),
            $device->getName(),
        );
    }
}

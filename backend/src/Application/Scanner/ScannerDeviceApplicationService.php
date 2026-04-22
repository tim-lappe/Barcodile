<?php

declare(strict_types=1);

namespace App\Application\Scanner;

use App\Application\Scanner\Dto\InputDeviceOptionResponse;
use App\Application\Scanner\Dto\ScannerDeviceResponse;
use App\Domain\Scanner\Entity\ScannerDevice;
use App\Domain\Scanner\Entity\ScannerDeviceId;
use App\Domain\Scanner\Port\InputDeviceListingPort;
use App\Domain\Scanner\Repository\ScannerDeviceRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class ScannerDeviceApplicationService
{
    public function __construct(
        private ScannerDeviceRepository $scannerDeviceRepository,
        private InputDeviceListingPort $inputDeviceListingPort,
    ) {
    }

    /**
     * @return list<ScannerDeviceResponse>
     */
    public function listScannerDevices(): array
    {
        $out = [];
        foreach ($this->scannerDeviceRepository->findAllOrderedByName() as $device) {
            $out[] = $this->map($device);
        }

        return $out;
    }

    /**
     * @return list<InputDeviceOptionResponse>
     */
    public function listInputDeviceOptions(): array
    {
        $out = [];
        foreach ($this->inputDeviceListingPort->listAvailableInputDevices() as $listed) {
            $out[] = new InputDeviceOptionResponse($listed->deviceIdentifier, $listed->label);
        }

        return $out;
    }

    public function createScannerDevice(string $deviceIdentifier, string $name): ScannerDeviceResponse
    {
        $device = new ScannerDevice();
        $device->changeDeviceIdentifier(trim($deviceIdentifier));
        $device->changeName(trim($name));
        $this->scannerDeviceRepository->save($device);

        return $this->map($device);
    }

    public function deleteScannerDevice(ScannerDeviceId $scannerDeviceId): void
    {
        $device = $this->scannerDeviceRepository->find($scannerDeviceId);
        if (!$device instanceof ScannerDevice) {
            throw new NotFoundHttpException('Scanner device not found.');
        }
        $this->scannerDeviceRepository->remove($device);
    }

    private function map(ScannerDevice $device): ScannerDeviceResponse
    {
        return new ScannerDeviceResponse(
            (string) $device->getId(),
            $device->getDeviceIdentifier(),
            $device->getName(),
            $device->getLastScannedCodes(),
        );
    }
}

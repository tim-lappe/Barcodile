<?php

declare(strict_types=1);

namespace App\Scanner\Application;

use App\Scanner\Api\Dto\InputDeviceOptionResponse;
use App\Scanner\Api\Dto\PatchScannerDeviceAutomationsRequest;
use App\Scanner\Api\Dto\ScannerDeviceResponse;
use App\Scanner\Domain\Facade\InputDeviceOptionView;
use App\Scanner\Domain\Facade\ScannerDeviceFacade;
use App\Scanner\Domain\Facade\ScannerDeviceView;

final readonly class ScannerDeviceApplicationService
{
    public function __construct(
        private ScannerDeviceFacade $scannerDevices,
    ) {
    }

    /**
     * @return list<ScannerDeviceResponse>
     */
    public function listScannerDevices(): array
    {
        return array_map(fn (ScannerDeviceView $device): ScannerDeviceResponse => $this->map($device), $this->scannerDevices->listScannerDevices());
    }

    /**
     * @return list<InputDeviceOptionResponse>
     */
    public function listInputDeviceOptions(): array
    {
        return array_map(
            static fn (InputDeviceOptionView $listed): InputDeviceOptionResponse => new InputDeviceOptionResponse($listed->deviceIdentifier, $listed->label),
            $this->scannerDevices->listInputDeviceOptions(),
        );
    }

    public function createScannerDevice(string $deviceIdentifier, string $name): ScannerDeviceResponse
    {
        return $this->map($this->scannerDevices->createScannerDevice($deviceIdentifier, $name));
    }

    public function deleteScannerDevice(string $scannerDeviceId): void
    {
        $this->scannerDevices->deleteScannerDevice($scannerDeviceId);
    }

    public function getScannerDevice(string $scannerDeviceId): ScannerDeviceResponse
    {
        return $this->map($this->scannerDevices->getScannerDevice($scannerDeviceId));
    }

    public function patchScannerDeviceAutomations(
        string $scannerDeviceId,
        PatchScannerDeviceAutomationsRequest $request,
    ): ScannerDeviceResponse {
        return $this->map($this->scannerDevices->patchScannerDeviceAutomations(
            $scannerDeviceId,
            $request->addOnEan,
            $request->createIfMissingEan,
            $request->remOnPublic,
        ));
    }

    private function map(ScannerDeviceView $device): ScannerDeviceResponse
    {
        return new ScannerDeviceResponse(
            $device->resourceId,
            $device->deviceIdentifier,
            $device->name,
            $device->lastScannedCodes,
            addOnEan: $device->addOnEan,
            createIfMissingEan: $device->createIfMissingEan,
            remOnPublic: $device->remOnPublic,
        );
    }
}

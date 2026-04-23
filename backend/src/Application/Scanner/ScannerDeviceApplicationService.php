<?php

declare(strict_types=1);

namespace App\Application\Scanner;

use App\Application\Scanner\Dto\InputDeviceOptionResponse;
use App\Application\Scanner\Dto\PatchScannerDeviceAutomationsRequest;
use App\Application\Scanner\Dto\ScannerDeviceResponse;
use App\Domain\Scanner\Entity\ScannerDevice;
use App\Domain\Scanner\Port\InputDeviceListingPort;
use App\Domain\Scanner\Repository\ScannerDeviceRepository;
use App\Domain\Shared\Id\ScannerDeviceId;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class ScannerDeviceApplicationService
{
    public function __construct(
        private ScannerDeviceRepository $deviceRepository,
        private InputDeviceListingPort $listingPort,
    ) {
    }

    /**
     * @return list<ScannerDeviceResponse>
     */
    public function listScannerDevices(): array
    {
        $out = [];
        foreach ($this->deviceRepository->findAllOrderedByName() as $device) {
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
        foreach ($this->listingPort->listAvailableInputDevices() as $listed) {
            $out[] = new InputDeviceOptionResponse($listed->deviceIdentifier, $listed->label);
        }

        return $out;
    }

    public function createScannerDevice(string $deviceIdentifier, string $name): ScannerDeviceResponse
    {
        $device = new ScannerDevice();
        $device->changeDeviceIdentifier(trim($deviceIdentifier));
        $device->changeName(trim($name));
        $this->deviceRepository->save($device);

        return $this->map($device);
    }

    public function deleteScannerDevice(ScannerDeviceId $scannerDeviceId): void
    {
        $device = $this->deviceRepository->find($scannerDeviceId);
        if (!$device instanceof ScannerDevice) {
            throw new NotFoundHttpException('Scanner device not found.');
        }
        $this->deviceRepository->remove($device);
    }

    public function getScannerDevice(ScannerDeviceId $scannerDeviceId): ScannerDeviceResponse
    {
        $device = $this->deviceRepository->find($scannerDeviceId);
        if (!$device instanceof ScannerDevice) {
            throw new NotFoundHttpException('Scanner device not found.');
        }

        return $this->map($device);
    }

    public function patchScannerDeviceAutomations(
        ScannerDeviceId $scannerDeviceId,
        PatchScannerDeviceAutomationsRequest $request,
    ): ScannerDeviceResponse {
        $device = $this->deviceRepository->find($scannerDeviceId);
        if (!$device instanceof ScannerDevice) {
            throw new NotFoundHttpException('Scanner device not found.');
        }
        $device->changeAutomationAddInventoryOnEanScan($request->addOnEan);
        if ($device->isAutomationAddInventoryOnEanScan()) {
            $device->changeAutomationCreateCatalogItemIfMissingForEan($request->createIfMissingEan);
        }
        $device->changeAutomationRemoveInventoryOnPublicCodeScan($request->remOnPublic);
        $this->deviceRepository->save($device);

        return $this->map($device);
    }

    private function map(ScannerDevice $device): ScannerDeviceResponse
    {
        return new ScannerDeviceResponse(
            (string) $device->getId(),
            $device->getDeviceIdentifier(),
            $device->getName(),
            $device->getLastScannedCodes(),
            addOnEan: $device->isAutomationAddInventoryOnEanScan(),
            createIfMissingEan: $device->isAutomationCreateCatalogItemIfMissingForEan(),
            remOnPublic: $device->isAutomationRemoveInventoryOnPublicCodeScan(),
        );
    }
}

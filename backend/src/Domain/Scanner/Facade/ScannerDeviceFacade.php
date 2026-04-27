<?php

declare(strict_types=1);

namespace App\Domain\Scanner\Facade;

use App\Domain\Scanner\Entity\ScannerDevice;
use App\Domain\Scanner\Port\InputDeviceListingPort;
use App\Domain\Scanner\Repository\ScannerDeviceRepository;
use App\Domain\Shared\Id\ScannerDeviceId;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class ScannerDeviceFacade
{
    public function __construct(
        private ScannerDeviceRepository $deviceRepository,
        private InputDeviceListingPort $listingPort,
    ) {
    }

    /**
     * @return list<ScannerDeviceView>
     */
    public function listScannerDevices(): array
    {
        return array_map(
            fn (ScannerDevice $device): ScannerDeviceView => $this->map($device),
            $this->deviceRepository->findAllOrderedByName(),
        );
    }

    /**
     * @return list<InputDeviceOptionView>
     */
    public function listInputDeviceOptions(): array
    {
        $out = [];
        foreach ($this->listingPort->listAvailableInputDevices() as $listed) {
            $out[] = new InputDeviceOptionView($listed->deviceIdentifier, $listed->label);
        }

        return $out;
    }

    public function createScannerDevice(string $deviceIdentifier, string $name): ScannerDeviceView
    {
        $device = new ScannerDevice();
        $device->changeDeviceIdentifier(trim($deviceIdentifier));
        $device->changeName(trim($name));
        $this->deviceRepository->save($device);

        return $this->map($device);
    }

    public function deleteScannerDevice(string $scannerDeviceId): void
    {
        $this->deviceRepository->remove($this->mustFind($scannerDeviceId));
    }

    public function getScannerDevice(string $scannerDeviceId): ScannerDeviceView
    {
        return $this->map($this->mustFind($scannerDeviceId));
    }

    public function patchScannerDeviceAutomations(
        string $scannerDeviceId,
        bool $addOnEan,
        bool $createIfMissingEan,
        bool $remOnPublic,
    ): ScannerDeviceView {
        $device = $this->mustFind($scannerDeviceId);
        $device->changeAutomationAddInventoryOnEanScan($addOnEan);
        if ($device->isAutomationAddInventoryOnEanScan()) {
            $device->changeAutomationCreateCatalogItemIfMissingForEan($createIfMissingEan);
        }
        $device->changeAutomationRemoveInventoryOnPublicCodeScan($remOnPublic);
        $this->deviceRepository->save($device);

        return $this->map($device);
    }

    private function mustFind(string $scannerDeviceId): ScannerDevice
    {
        $device = $this->deviceRepository->find(ScannerDeviceId::fromString($scannerDeviceId));
        if (!$device instanceof ScannerDevice) {
            throw new NotFoundHttpException('Scanner device not found.');
        }

        return $device;
    }

    private function map(ScannerDevice $device): ScannerDeviceView
    {
        return new ScannerDeviceView(
            (string) $device->getId(),
            $device->getDeviceIdentifier(),
            $device->getName(),
            $device->getLastScannedCodes(),
            $device->isAutomationAddInventoryOnEanScan(),
            $device->isAutomationCreateCatalogItemIfMissingForEan(),
            $device->isAutomationRemoveInventoryOnPublicCodeScan(),
        );
    }
}

<?php

declare(strict_types=1);

namespace App\Scanner\Application;

use App\Scanner\Application\Dto\InputDeviceOptionResponse;
use App\Scanner\Application\Dto\PatchScannerDeviceAutomationsRequest;
use App\Scanner\Application\Dto\PostScannerDeviceSimulateInputRequest;
use App\Scanner\Application\Dto\ScannerDeviceResponse;
use App\Scanner\Domain\Entity\ScannerDevice;
use App\Scanner\Domain\Input\ScannerInputReceiver;
use App\Scanner\Domain\Port\InputDeviceListingPort;
use App\Scanner\Domain\Repository\ScannerDeviceRepository;
use App\SharedKernel\Domain\Id\PrinterDeviceId;
use App\SharedKernel\Domain\Id\ScannerDeviceId;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class ScannerDeviceApplicationService
{
    public function __construct(
        private ScannerDeviceRepository $scannerDevices,
        private InputDeviceListingPort $listingPort,
        private ScannerInputReceiver $scannerInputReceiver,
    ) {
    }

    /**
     * @return list<ScannerDeviceResponse>
     */
    public function listScannerDevices(): array
    {
        return array_map(
            fn (ScannerDevice $device): ScannerDeviceResponse => $this->map($this->mapDevice($device)),
            $this->scannerDevices->findAllOrderedByName(),
        );
    }

    /**
     * @return list<InputDeviceOptionResponse>
     */
    public function listInputDeviceOptions(): array
    {
        return array_map(
            static fn (InputDeviceOptionView $listed): InputDeviceOptionResponse => new InputDeviceOptionResponse($listed->deviceIdentifier, $listed->label),
            $this->inputDeviceOptionViews(),
        );
    }

    public function createScannerDevice(string $deviceIdentifier, string $name): ScannerDeviceResponse
    {
        $device = new ScannerDevice();
        $device->changeDeviceIdentifier(trim($deviceIdentifier));
        $device->changeName(trim($name));
        $this->scannerDevices->save($device);

        return $this->map($this->mapDevice($device));
    }

    public function deleteScannerDevice(string $scannerDeviceId): void
    {
        $this->scannerDevices->remove($this->mustFind($scannerDeviceId));
    }

    public function getScannerDevice(string $scannerDeviceId): ScannerDeviceResponse
    {
        return $this->map($this->mapDevice($this->mustFind($scannerDeviceId)));
    }

    public function patchScannerDeviceAutomations(
        string $scannerDeviceId,
        PatchScannerDeviceAutomationsRequest $request,
    ): ScannerDeviceResponse {
        $device = $this->mustFind($scannerDeviceId);
        $this->applyAutomationPatch($device, $request);
        $this->scannerDevices->save($device);

        return $this->map($this->mapDevice($device));
    }

    public function simulateScannerInput(
        string $scannerDeviceId,
        PostScannerDeviceSimulateInputRequest $request,
    ): ScannerDeviceResponse {
        $device = $this->mustFind($scannerDeviceId);
        $this->scannerInputReceiver->receiveInput($device->getId(), $request->text);

        return $this->map($this->mapDevice($device));
    }

    private function map(ScannerDeviceView $device): ScannerDeviceResponse
    {
        return new ScannerDeviceResponse(
            $device->resourceId,
            $device->deviceIdentifier,
            $device->name,
            $device->lastScannedCodes,
            addOnBarcodeScan: $device->addOnBarcodeScan,
            createIfMissingBarcode: $device->createIfMissingBarcode,
            remOnPublic: $device->remOnPublic,
            printLabelOnBarcodeScan: $device->printLabelOnBarcodeScan,
            printerDeviceId: $device->printerDeviceId,
        );
    }

    /**
     * @return list<InputDeviceOptionView>
     */
    private function inputDeviceOptionViews(): array
    {
        $out = [];
        foreach ($this->listingPort->listAvailableInputDevices() as $listed) {
            $out[] = new InputDeviceOptionView($listed->deviceIdentifier, $listed->label);
        }

        return $out;
    }

    private function applyAutomationPatch(ScannerDevice $device, PatchScannerDeviceAutomationsRequest $request): void
    {
        $device->changeAutomationAddInventoryOnBarcodeScan($request->addOnBarcodeScan);
        $this->applyBarcodeAutomationPatch($device, $request);
        $device->changeAutomationRemoveInventoryOnPublicCodeScan($request->remOnPublic);
    }

    private function applyBarcodeAutomationPatch(ScannerDevice $device, PatchScannerDeviceAutomationsRequest $request): void
    {
        if (!$device->isAutomationAddInventoryOnBarcodeScan()) {
            return;
        }
        $device->changeAutomationCreateCatalogItemIfMissingForBarcode($request->createIfMissingBarcode);
        $device->changeAutomationPrintInventoryLabelOnBarcodeScan($request->printLabelOnBarcodeScan);
        if ($device->isAutomationPrintInventoryLabelOnBarcodeScan()) {
            $device->changeAutomationPrinterDeviceId(null === $request->printerDeviceId ? null : PrinterDeviceId::fromString($request->printerDeviceId));
        }
    }

    private function mustFind(string $scannerDeviceId): ScannerDevice
    {
        $device = $this->scannerDevices->find(ScannerDeviceId::fromString($scannerDeviceId));
        if (!$device instanceof ScannerDevice) {
            throw new NotFoundHttpException('Scanner device not found.');
        }

        return $device;
    }

    private function mapDevice(ScannerDevice $device): ScannerDeviceView
    {
        return new ScannerDeviceView(
            (string) $device->getId(),
            $device->getDeviceIdentifier(),
            $device->getName(),
            $device->getLastScannedCodes(),
            $device->isAutomationAddInventoryOnBarcodeScan(),
            $device->isAutomationCreateCatalogItemIfMissingForBarcode(),
            $device->isAutomationRemoveInventoryOnPublicCodeScan(),
            $device->isAutomationPrintInventoryLabelOnBarcodeScan(),
            null === $device->getAutomationPrinterDeviceId() ? null : (string) $device->getAutomationPrinterDeviceId(),
        );
    }
}

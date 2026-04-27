<?php

declare(strict_types=1);

namespace App\Scanner\Infrastructure;

use App\Catalog\Application\CatalogItemApplicationService;
use App\Catalog\Domain\Repository\CatalogItemRepository;
use App\SharedKernel\Domain\Barcode;
use App\Inventory\Application\InventoryItemApplicationService;
use App\Inventory\Domain\Repository\InventoryItemRepository;
use App\Inventory\Domain\ValueObject\InventoryItemCode;
use App\Scanner\Domain\Entity\ScannerDevice;
use App\Scanner\Domain\Events\CodeScanned;
use App\Scanner\Domain\Repository\ScannerDeviceRepository;

final readonly class ScannerInvAutomationService
{
    public function __construct(
        private ScannerDeviceRepository $deviceRepository,
        private InventoryItemRepository $invItemRepo,
        private CatalogItemRepository $catItemRepo,
        private InventoryItemApplicationService $inventoryItems,
        private CatalogItemApplicationService $catalog,
    ) {
    }

    public function handleCodeScanned(CodeScanned $event): void
    {
        $scan = $this->scannedCode($event);
        if (null === $scan) {
            return;
        }
        if ($this->tryRemoveByPublicCode($scan->device, $scan->text)) {
            return;
        }
        if (!$scan->device->isAutomationAddInventoryOnBarcodeScan()) {
            return;
        }
        $this->addInventoryItemForBarcodeScan($scan->device, $scan->text);
    }

    private function addInventoryItemForBarcodeScan(ScannerDevice $device, string $text): void
    {
        $catalogItemId = $this->findOrCreateCatalogItemIdForScannedCode($device, $text);
        if (null === $catalogItemId) {
            return;
        }
        $inventoryItemId = $this->inventoryItems->createInventoryItem($catalogItemId, null, null);
        $this->printInventoryLabelIfEnabled($device, $inventoryItemId);
    }

    private function printInventoryLabelIfEnabled(ScannerDevice $device, string $inventoryItemId): void
    {
        $printerDeviceId = $device->getAutomationPrinterDeviceId();
        if ($device->isAutomationPrintInventoryLabelOnBarcodeScan() && null !== $printerDeviceId) {
            $this->inventoryItems->printInventoryItemLabel($inventoryItemId, (string) $printerDeviceId);
        }
    }

    private function findOrCreateCatalogItemIdForScannedCode(ScannerDevice $device, string $text): ?string
    {
        $row = $this->catItemRepo->findOneByBarcodeCodeCaseInsensitive($text);
        if (null !== $row) {
            return (string) $row->getId();
        }
        if (!$device->isAutomationCreateCatalogItemIfMissingForBarcode()) {
            return null;
        }
        $created = $this->catalog->createCatalogItemFromValues(
            'Item '.$text,
            null,
            null,
            null,
            null,
            $text,
            Barcode::DEFAULT_SYMBOLOGY,
            null,
            null,
            'manual',
        );

        return $created->resourceId;
    }

    private function tryRemoveByPublicCode(ScannerDevice $device, string $text): bool
    {
        if (!$device->isAutomationRemoveInventoryOnPublicCodeScan() || !$this->isDigitsOnly($text)) {
            return false;
        }
        $item = $this->invItemRepo->findOneByPublicCode(new InventoryItemCode($text));
        if (null === $item) {
            return false;
        }
        $this->inventoryItems->deleteInventoryItem((string) $item->getId());

        return true;
    }

    private function isDigitsOnly(string $text): bool
    {
        return '' !== $text && 1 === preg_match('/^\d+$/', $text);
    }

    private function scannedCode(CodeScanned $event): ?ScannedCode
    {
        $device = $this->deviceRepository->find($event->scannerDeviceId);
        $text = trim($event->text);
        if (null === $device || '' === $text) {
            return null;
        }

        return new ScannedCode($device, $text);
    }
}

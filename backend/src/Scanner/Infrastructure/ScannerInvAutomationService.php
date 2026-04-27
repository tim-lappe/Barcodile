<?php

declare(strict_types=1);

namespace App\Scanner\Infrastructure;

use App\Catalog\Application\CatalogItemApplicationService;
use App\Catalog\Domain\Repository\CatalogItemRepository;
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

    /**
     * @SuppressWarnings("PHPMD.CyclomaticComplexity")
     */
    public function handleCodeScanned(CodeScanned $event): void
    {
        $device = $this->deviceRepository->find($event->scannerDeviceId);
        $text = trim($event->text);
        if (null === $device || '' === $text) {
            return;
        }
        if ($this->tryRemoveByPublicCode($device, $text)) {
            return;
        }
        if (!$device->isAutomationAddInventoryOnEanScan()) {
            return;
        }
        $catalogItemId = $this->findOrCreateEanCatalogItemId($device, $text);
        if (null === $catalogItemId) {
            return;
        }
        $newInventoryId = $this->inventoryItems->createInventoryItem($catalogItemId, null, null);
        if ($device->isAutomationPrintLabelAfterEanAddInventory()) {
            $printerId = $device->getAutomationLabelPrinterDeviceId();
            if (null !== $printerId) {
                $this->inventoryItems->printInventoryItemLabel($newInventoryId, (string) $printerId);
            }
        }
    }

    private function findOrCreateEanCatalogItemId(ScannerDevice $device, string $text): ?string
    {
        $row = $this->catItemRepo->findOneByBarcodeCodeAndTypeCaseInsensitive($text, 'EAN');
        if (null !== $row) {
            return (string) $row->getId();
        }
        if (!$device->isAutomationCreateCatalogItemIfMissingForEan()) {
            return null;
        }
        $created = $this->catalog->createCatalogItemFromValues(
            'EAN '.$text,
            null,
            null,
            null,
            null,
            $text,
            'EAN',
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
}

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
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

final readonly class InventoryAutomationOnCodeScanned
{
    #[AsEventListener]
    public function __invoke(
        CodeScanned $event,
        ScannerDeviceRepository $deviceRepository,
        InventoryItemRepository $invItemRepo,
        CatalogItemRepository $catItemRepo,
        InventoryItemApplicationService $inventoryItems,
        CatalogItemApplicationService $catalog,
    ): void {
        $this->runAutomation(
            $event,
            $deviceRepository,
            $invItemRepo,
            $catItemRepo,
            $inventoryItems,
            $catalog,
        );
    }

    private function runAutomation(
        CodeScanned $event,
        ScannerDeviceRepository $deviceRepository,
        InventoryItemRepository $invItemRepo,
        CatalogItemRepository $catItemRepo,
        InventoryItemApplicationService $inventoryItems,
        CatalogItemApplicationService $catalog,
    ): void {
        $device = $deviceRepository->find($event->scannerDeviceId);
        $text = trim($event->text);
        if (null === $device || '' === $text) {
            return;
        }
        if ($this->tryRemoveByPublicCode($device, $text, $invItemRepo, $inventoryItems)) {
            return;
        }
        if (!$device->isAutomationAddInventoryOnEanScan()) {
            return;
        }
        $catalogItemId = $this->findOrCreateEanCatalogItemId($device, $text, $catItemRepo, $catalog);
        if (null === $catalogItemId) {
            return;
        }
        $newInventoryId = $inventoryItems->createInventoryItem($catalogItemId, null, null);
        if (!$device->isAutomationPrintLabelAfterEanAddInventory()) {
            return;
        }
        $printerId = $device->getAutomationLabelPrinterDeviceId();
        if (null === $printerId) {
            return;
        }
        $inventoryItems->printInventoryItemLabel($newInventoryId, (string) $printerId);
    }

    private function findOrCreateEanCatalogItemId(
        ScannerDevice $device,
        string $text,
        CatalogItemRepository $catItemRepo,
        CatalogItemApplicationService $catalog,
    ): ?string {
        $row = $catItemRepo->findOneByBarcodeCodeAndTypeCaseInsensitive($text, 'EAN');
        if (null !== $row) {
            return (string) $row->getId();
        }
        if (!$device->isAutomationCreateCatalogItemIfMissingForEan()) {
            return null;
        }
        $created = $catalog->createCatalogItemFromBarcodeWithPlaceholderFallback($text, 'EAN');

        return $created->resourceId;
    }

    private function tryRemoveByPublicCode(
        ScannerDevice $device,
        string $text,
        InventoryItemRepository $invItemRepo,
        InventoryItemApplicationService $inventoryItems,
    ): bool {
        if (!$device->isAutomationRemoveInventoryOnPublicCodeScan() || !$this->isDigitsOnly($text)) {
            return false;
        }
        $item = $invItemRepo->findOneByPublicCode(new InventoryItemCode($text));
        if (null === $item) {
            return false;
        }
        $inventoryItems->deleteInventoryItem((string) $item->getId());

        return true;
    }

    private function isDigitsOnly(string $text): bool
    {
        return '' !== $text && 1 === preg_match('/^\d+$/', $text);
    }
}

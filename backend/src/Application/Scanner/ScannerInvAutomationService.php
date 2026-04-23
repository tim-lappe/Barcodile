<?php

declare(strict_types=1);

namespace App\Application\Scanner;

use App\Application\Catalog\CatalogItemApplicationService;
use App\Application\Catalog\CatalogItemCreationSource;
use App\Application\Catalog\Dto\CatalogBarcodeInput;
use App\Application\Catalog\Dto\PostCatalogItemRequest;
use App\Application\Inventory\InventoryItemApplicationService;
use App\Domain\Catalog\Repository\CatalogItemRepository;
use App\Domain\Inventory\Repository\InventoryItemRepository;
use App\Domain\Scanner\Entity\ScannerDevice;
use App\Domain\Scanner\Events\CodeScanned;
use App\Domain\Scanner\Repository\ScannerDeviceRepository;
use App\Domain\Shared\Id\CatalogItemId;

final readonly class ScannerInvAutomationService
{
    public function __construct(
        private ScannerDeviceRepository $deviceRepository,
        private InventoryItemRepository $invItemRepo,
        private CatalogItemRepository $catItemRepo,
        private InventoryItemApplicationService $invItemApp,
        private CatalogItemApplicationService $catItemApp,
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
        $this->invItemApp->createInventoryItem($catalogItemId, null, null);
    }

    private function findOrCreateEanCatalogItemId(ScannerDevice $device, string $text): ?CatalogItemId
    {
        $row = $this->catItemRepo->findOneByBarcodeCodeAndTypeCaseInsensitive($text, 'EAN');
        if (null !== $row) {
            return $row->getId();
        }
        if (!$device->isAutomationCreateCatalogItemIfMissingForEan()) {
            return null;
        }
        $created = $this->catItemApp->createCatalogItem(
            new PostCatalogItemRequest(
                name: 'EAN '.$text,
                volume: null,
                weight: null,
                barcode: new CatalogBarcodeInput($text, 'EAN'),
                itemAttributes: null,
                picnicProductLink: null,
                creationSource: CatalogItemCreationSource::Manual,
            ),
        );

        return CatalogItemId::fromString($created->resourceId);
    }

    private function tryRemoveByPublicCode(ScannerDevice $device, string $text): bool
    {
        if (!$device->isAutomationRemoveInventoryOnPublicCodeScan() || !$this->isDigitsOnly($text)) {
            return false;
        }
        $item = $this->invItemRepo->findOneByPublicCode($text);
        if (null === $item) {
            return false;
        }
        $this->invItemApp->deleteInventoryItem($item->getId());

        return true;
    }

    private function isDigitsOnly(string $text): bool
    {
        return '' !== $text && 1 === preg_match('/^\d+$/', $text);
    }
}

<?php

declare(strict_types=1);

namespace App\Scanner\Infrastructure;

use App\Catalog\Api\Dto\CatalogBarcodeInput;
use App\Catalog\Api\Dto\PostCatalogItemRequest;
use App\Catalog\Application\CatalogItemApplicationService;
use App\Catalog\Application\CatalogItemCreationSource;
use App\Catalog\Domain\Repository\CatalogItemRepository;
use App\Inventory\Application\InventoryItemApplicationService;
use App\Inventory\Domain\Repository\InventoryItemRepository;
use App\Scanner\Domain\Entity\ScannerDevice;
use App\Scanner\Domain\Events\CodeScanned;
use App\Scanner\Domain\Repository\ScannerDeviceRepository;

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

    private function findOrCreateEanCatalogItemId(ScannerDevice $device, string $text): ?string
    {
        $row = $this->catItemRepo->findOneByBarcodeCodeAndTypeCaseInsensitive($text, 'EAN');
        if (null !== $row) {
            return (string) $row->getId();
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

        return $created->resourceId;
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
        $this->invItemApp->deleteInventoryItem((string) $item->getId());

        return true;
    }

    private function isDigitsOnly(string $text): bool
    {
        return '' !== $text && 1 === preg_match('/^\d+$/', $text);
    }
}

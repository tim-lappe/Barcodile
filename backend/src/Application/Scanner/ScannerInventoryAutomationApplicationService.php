<?php

declare(strict_types=1);

namespace App\Application\Scanner;

use App\Application\Catalog\CatalogItemApplicationService;
use App\Application\Catalog\CatalogItemCreationSource;
use App\Application\Catalog\Dto\CatalogBarcodeInput;
use App\Application\Catalog\Dto\PostCatalogItemRequest;
use App\Application\Inventory\InventoryItemApplicationService;
use App\Domain\Catalog\Entity\CatalogItemId;
use App\Domain\Catalog\Repository\CatalogItemRepository;
use App\Domain\Inventory\Repository\InventoryItemRepository;
use App\Domain\Scanner\Events\CodeScanned;
use App\Domain\Scanner\Repository\ScannerDeviceRepository;

final readonly class ScannerInventoryAutomationApplicationService
{
    public function __construct(
        private ScannerDeviceRepository $scannerDeviceRepository,
        private InventoryItemRepository $inventoryItemRepository,
        private CatalogItemRepository $catalogItemRepository,
        private InventoryItemApplicationService $inventoryItemApplicationService,
        private CatalogItemApplicationService $catalogItemApplicationService,
    ) {
    }

    public function handleCodeScanned(CodeScanned $event): void
    {
        $device = $this->scannerDeviceRepository->find($event->scannerDeviceId);
        if (null === $device) {
            return;
        }

        $text = trim($event->text);
        if ('' === $text) {
            return;
        }

        if ($device->isAutomationRemoveInventoryOnPublicCodeScan() && $this->isDigitsOnly($text)) {
            $item = $this->inventoryItemRepository->findOneByPublicCode($text);
            if (null !== $item) {
                $this->inventoryItemApplicationService->deleteInventoryItem($item->getId());

                return;
            }
        }

        if (!$device->isAutomationAddInventoryOnEanScan()) {
            return;
        }

        $catalogItem = $this->catalogItemRepository->findOneByBarcodeCodeAndTypeCaseInsensitive($text, 'EAN');
        if (null === $catalogItem) {
            if (!$device->isAutomationCreateCatalogItemIfMissingForEan()) {
                return;
            }
            $created = $this->catalogItemApplicationService->createCatalogItem(
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
            $catalogItemId = CatalogItemId::fromString($created->resourceId);
        } else {
            $catalogItemId = $catalogItem->getId();
        }

        $this->inventoryItemApplicationService->createInventoryItem($catalogItemId, null, null);
    }

    private function isDigitsOnly(string $text): bool
    {
        return '' !== $text && 1 === preg_match('/^\d+$/', $text);
    }
}

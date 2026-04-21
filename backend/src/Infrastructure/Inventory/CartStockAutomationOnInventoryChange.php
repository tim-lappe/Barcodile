<?php

declare(strict_types=1);

namespace App\Infrastructure\Inventory;

use App\Application\Inventory\Service\CartStockAutomationApplicationService;
use App\Domain\Catalog\Entity\CatalogItemId;
use App\Domain\Inventory\Events\InventoryItemCatalogItemChanged;
use App\Domain\Inventory\Events\InventoryItemCreated;
use App\Domain\Inventory\Events\InventoryItemDeleted;
use App\Domain\Inventory\Events\InventoryItemQuantityChanged;
use App\Domain\Inventory\Repository\InventoryItemRepository;
use App\Domain\Shared\Math\BcQuantity;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

final readonly class CartStockAutomationOnInventoryChange
{
    private const int BC_SCALE = 4;

    public function __construct(
        private InventoryItemRepository $itemRepo,
        private CartStockAutomationApplicationService $stockAutomationSvc,
    ) {
    }

    #[AsEventListener]
    public function onQuantityChanged(InventoryItemQuantityChanged $event): void
    {
        $item = $event->inventoryItem;
        $catalogId = $item->getCatalogItem()?->getId();
        if (!$catalogId instanceof CatalogItemId) {
            return;
        }
        $newTotal = $this->itemRepo->sumQuantityForCatalogItem($catalogId);
        $previousTotal = BcQuantity::add(
            BcQuantity::sub($newTotal, $event->newQuantity, self::BC_SCALE),
            $event->previousQuantity,
            self::BC_SCALE,
        );
        $this->stockAutomationSvc->onAggregateTotalsChanged($catalogId, $previousTotal, $newTotal);
    }

    #[AsEventListener]
    public function onCreated(InventoryItemCreated $event): void
    {
        $item = $event->inventoryItem;
        $catalogId = $item->getCatalogItem()?->getId();
        if (!$catalogId instanceof CatalogItemId) {
            return;
        }
        $newTotal = $this->itemRepo->sumQuantityForCatalogItem($catalogId);
        $previousTotal = BcQuantity::sub($newTotal, $item->getQuantity(), self::BC_SCALE);
        $this->stockAutomationSvc->onAggregateTotalsChanged($catalogId, $previousTotal, $newTotal);
    }

    #[AsEventListener]
    public function onDeleted(InventoryItemDeleted $event): void
    {
        $newTotal = $this->itemRepo->sumQuantityForCatalogItem($event->catalogItemId);
        $previousTotal = BcQuantity::add($newTotal, $event->lastQuantity, self::BC_SCALE);
        $this->stockAutomationSvc->onAggregateTotalsChanged($event->catalogItemId, $previousTotal, $newTotal);
    }

    #[AsEventListener]
    public function onCatalogItemChanged(InventoryItemCatalogItemChanged $event): void
    {
        $item = $event->inventoryItem;
        $qty = $item->getQuantity();
        $prevCat = $event->previousCatalogItem?->getId();
        $newCat = $event->newCatalogItem?->getId();
        if ($prevCat instanceof CatalogItemId) {
            $newForPrev = $this->itemRepo->sumQuantityForCatalogItem($prevCat);
            $previousForPrev = BcQuantity::add($newForPrev, $qty, self::BC_SCALE);
            $this->stockAutomationSvc->onAggregateTotalsChanged($prevCat, $previousForPrev, $newForPrev);
        }
        if ($newCat instanceof CatalogItemId) {
            $newForNew = $this->itemRepo->sumQuantityForCatalogItem($newCat);
            $previousForNew = BcQuantity::sub($newForNew, $qty, self::BC_SCALE);
            $this->stockAutomationSvc->onAggregateTotalsChanged($newCat, $previousForNew, $newForNew);
        }
    }
}

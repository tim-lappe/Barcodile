<?php

declare(strict_types=1);

namespace App\Infrastructure\Inventory;

use App\Application\Inventory\Service\CartStockAutomationApplicationService;
use App\Domain\Catalog\Entity\CatalogItemId;
use App\Domain\Inventory\Events\InventoryItemCatalogItemChanged;
use App\Domain\Inventory\Events\InventoryItemCreated;
use App\Domain\Inventory\Events\InventoryItemDeleted;
use App\Domain\Inventory\Repository\InventoryItemRepository;
use App\Domain\Shared\Math\BcQuantity;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

final readonly class CartStockAutomationOnInventoryChange
{
    private const int BC_SCALE = 4;

    private const string ONE = '1';

    public function __construct(
        private InventoryItemRepository $itemRepo,
        private CartStockAutomationApplicationService $stockAutomationSvc,
    ) {
    }

    #[AsEventListener]
    public function onCreated(InventoryItemCreated $event): void
    {
        $item = $event->inventoryItem;
        $catalogId = $item->getCatalogItem()?->getId();
        if (!$catalogId instanceof CatalogItemId) {
            return;
        }
        $newTotal = (string) $this->itemRepo->countForCatalogItem($catalogId);
        $previousTotal = BcQuantity::sub($newTotal, self::ONE, self::BC_SCALE);
        $this->stockAutomationSvc->onAggregateTotalsChanged($catalogId, $previousTotal, $newTotal);
    }

    #[AsEventListener]
    public function onDeleted(InventoryItemDeleted $event): void
    {
        $newTotal = (string) $this->itemRepo->countForCatalogItem($event->catalogItemId);
        $previousTotal = BcQuantity::add($newTotal, self::ONE, self::BC_SCALE);
        $this->stockAutomationSvc->onAggregateTotalsChanged($event->catalogItemId, $previousTotal, $newTotal);
    }

    #[AsEventListener]
    public function onCatalogItemChanged(InventoryItemCatalogItemChanged $event): void
    {
        $prevCat = $event->previousCatalogItem?->getId();
        $newCat = $event->newCatalogItem?->getId();
        if ($prevCat instanceof CatalogItemId) {
            $newForPrev = (string) $this->itemRepo->countForCatalogItem($prevCat);
            $previousForPrev = BcQuantity::add($newForPrev, self::ONE, self::BC_SCALE);
            $this->stockAutomationSvc->onAggregateTotalsChanged($prevCat, $previousForPrev, $newForPrev);
        }
        if ($newCat instanceof CatalogItemId) {
            $newForNew = (string) $this->itemRepo->countForCatalogItem($newCat);
            $previousForNew = BcQuantity::sub($newForNew, self::ONE, self::BC_SCALE);
            $this->stockAutomationSvc->onAggregateTotalsChanged($newCat, $previousForNew, $newForNew);
        }
    }
}

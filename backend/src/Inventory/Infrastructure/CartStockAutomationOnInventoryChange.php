<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure;

use App\Inventory\Application\Service\CartStockAutomationApplicationService;
use App\Inventory\Domain\Events\InventoryItemCatalogItemChanged;
use App\Inventory\Domain\Events\InventoryItemCreated;
use App\Inventory\Domain\Events\InventoryItemDeleted;
use App\Inventory\Domain\Repository\InventoryItemRepository;
use App\SharedKernel\Domain\Id\CatalogItemId;
use App\SharedKernel\Domain\Math\BcQuantity;
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
        $catalogId = $item->getCatalogItemId();
        if (!$catalogId instanceof CatalogItemId) {
            return;
        }
        $newTotal = (string) $this->itemRepo->countForCatalogItem($catalogId);
        $previousTotal = BcQuantity::sub($newTotal, self::ONE, self::BC_SCALE);
        $this->stockAutomationSvc->onAggregateTotalsChanged((string) $catalogId, $previousTotal, $newTotal);
    }

    #[AsEventListener]
    public function onDeleted(InventoryItemDeleted $event): void
    {
        $newTotal = (string) $this->itemRepo->countForCatalogItem($event->catalogItemId);
        $previousTotal = BcQuantity::add($newTotal, self::ONE, self::BC_SCALE);
        $this->stockAutomationSvc->onAggregateTotalsChanged((string) $event->catalogItemId, $previousTotal, $newTotal);
    }

    #[AsEventListener]
    public function onCatalogItemChanged(InventoryItemCatalogItemChanged $event): void
    {
        $prevCat = $event->previousCatalogId;
        $newCat = $event->newCatalogId;
        if ($prevCat instanceof CatalogItemId) {
            $newForPrev = (string) $this->itemRepo->countForCatalogItem($prevCat);
            $previousForPrev = BcQuantity::add($newForPrev, self::ONE, self::BC_SCALE);
            $this->stockAutomationSvc->onAggregateTotalsChanged((string) $prevCat, $previousForPrev, $newForPrev);
        }
        if ($newCat instanceof CatalogItemId) {
            $newForNew = (string) $this->itemRepo->countForCatalogItem($newCat);
            $previousForNew = BcQuantity::sub($newForNew, self::ONE, self::BC_SCALE);
            $this->stockAutomationSvc->onAggregateTotalsChanged((string) $newCat, $previousForNew, $newForNew);
        }
    }
}

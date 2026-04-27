<?php

declare(strict_types=1);

namespace App\Inventory\Application\Service;

use App\Inventory\Domain\Facade\InventoryFacade;

final readonly class CartStockAutomationApplicationService
{
    public function __construct(
        private InventoryFacade $inventory,
    ) {
    }

    public function onAggregateTotalsChanged(string $catalogItemId, string $previousTotal, string $newTotal): void
    {
        $this->inventory->onAggregateTotalsChanged($catalogItemId, $previousTotal, $newTotal);
    }
}

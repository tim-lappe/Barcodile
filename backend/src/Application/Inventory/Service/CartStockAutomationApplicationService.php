<?php

declare(strict_types=1);

namespace App\Application\Inventory\Service;

use App\Domain\Inventory\Facade\InventoryFacade;

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

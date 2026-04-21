<?php

declare(strict_types=1);

namespace App\Application\Inventory\Service;

use App\Domain\Cart\Repository\ShoppingCartRepository;
use App\Domain\Catalog\Entity\CatalogItemId;
use App\Domain\Inventory\Entity\CartStockAutomationRule;
use App\Domain\Inventory\Repository\CartStockAutomationRuleRepository;
use App\Domain\Shared\Math\BcQuantity;

final readonly class CartStockAutomationApplicationService
{
    private const int BC_SCALE = 4;

    public function __construct(
        private CartStockAutomationRuleRepository $ruleRepo,
        private ShoppingCartRepository $cartRepo,
    ) {
    }

    public function onAggregateTotalsChanged(CatalogItemId $catalogItemId, string $previousTotal, string $newTotal): void
    {
        $rules = $this->ruleRepo->findEnabledByCatalogItemId($catalogItemId);
        foreach ($rules as $rule) {
            $threshold = (string) $rule->getStockBelow();
            $wasAbove = BcQuantity::comp($previousTotal, $threshold, self::BC_SCALE) > 0;
            $nowAtOrBelow = BcQuantity::comp($newTotal, $threshold, self::BC_SCALE) <= 0;
            if (!$wasAbove || !$nowAtOrBelow) {
                continue;
            }
            $this->enqueueAddition($rule);
        }
    }

    private function enqueueAddition(CartStockAutomationRule $rule): void
    {
        $shoppingCart = $rule->getShoppingCart();
        $item = $rule->getCatalogItem();
        if (null === $shoppingCart || null === $item) {
            return;
        }
        $shoppingCart->mergeOrAddLineForCatalogItem($item, $rule->getAddQuantity());
        $this->cartRepo->save($shoppingCart);
    }
}

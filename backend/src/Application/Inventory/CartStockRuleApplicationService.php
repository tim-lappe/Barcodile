<?php

declare(strict_types=1);

namespace App\Application\Inventory;

use App\Application\Inventory\Dto\CartStockAutomationRuleResponse;
use App\Application\Inventory\Dto\PatchCartStockAutomationRuleRequest;
use App\Application\Shared\ApiIri;
use App\Domain\Inventory\Facade\CartStockAutomationRuleView;
use App\Domain\Inventory\Facade\InventoryFacade;

final readonly class CartStockRuleApplicationService
{
    public function __construct(
        private InventoryFacade $inventory,
    ) {
    }

    /**
     * @return list<CartStockAutomationRuleResponse>
     */
    public function listRules(string $catalogItemId): array
    {
        return array_map(fn (CartStockAutomationRuleView $rule): CartStockAutomationRuleResponse => $this->map($rule), $this->inventory->listCartStockRules($catalogItemId));
    }

    public function createRule(
        string $catalogItemId,
        string $shoppingCartId,
        int $stockBelow,
        int $addQuantity,
        bool $enabled,
    ): CartStockAutomationRuleResponse {
        return $this->map($this->inventory->createCartStockRule($catalogItemId, $shoppingCartId, $stockBelow, $addQuantity, $enabled));
    }

    public function patchRule(string $catalogItemId, string $ruleId, PatchCartStockAutomationRuleRequest $patch): void
    {
        $this->inventory->patchCartStockRule(
            $catalogItemId,
            $ruleId,
            $patch->cartInPatch,
            $patch->cartIri,
            $patch->stockBelowSpecified,
            $patch->stockBelow,
            $patch->addQuantitySpecified,
            $patch->addQuantity,
            $patch->enabledSpecified,
            $patch->enabled,
        );
    }

    public function deleteRule(string $catalogItemId, string $ruleId): void
    {
        $this->inventory->deleteCartStockRule($catalogItemId, $ruleId);
    }

    private function map(CartStockAutomationRuleView $rule): CartStockAutomationRuleResponse
    {
        return new CartStockAutomationRuleResponse(
            $rule->resourceId,
            ApiIri::catalogItem($rule->catalogItemId),
            ApiIri::shoppingCart($rule->shoppingCartId),
            $rule->stockBelow,
            $rule->addQuantity,
            $rule->enabled,
            $rule->createdAt,
        );
    }
}

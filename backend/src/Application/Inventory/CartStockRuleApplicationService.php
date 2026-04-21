<?php

declare(strict_types=1);

namespace App\Application\Inventory;

use App\Application\Inventory\Dto\CartStockAutomationRuleResponse;
use App\Application\Inventory\Dto\PatchCartStockAutomationRuleRequest;
use App\Application\Shared\ApiIri;
use App\Domain\Cart\Entity\ShoppingCart;
use App\Domain\Cart\Entity\ShoppingCartId;
use App\Domain\Cart\Repository\ShoppingCartRepository;
use App\Domain\Catalog\Entity\CatalogItem;
use App\Domain\Catalog\Entity\CatalogItemId;
use App\Domain\Inventory\Entity\CartStockAutomationRule;
use App\Domain\Inventory\Entity\CartStockAutomationRuleId;
use App\Domain\Inventory\Repository\CartStockAutomationRuleRepository;
use DateTimeInterface;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use LogicException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class CartStockRuleApplicationService
{
    public function __construct(
        private CartStockAutomationRuleRepository $automationRuleRepo,
        private ShoppingCartRepository $shoppingCartRepo,
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @return list<CartStockAutomationRuleResponse>
     */
    public function listRules(CatalogItemId $catalogItemId): array
    {
        $out = [];
        foreach ($this->automationRuleRepo->findAllByCatalogItemIdOrdered($catalogItemId) as $ruleEntity) {
            $out[] = $this->map($ruleEntity);
        }

        return $out;
    }

    public function createRule(
        CatalogItemId $catalogItemId,
        ShoppingCartId $shoppingCartId,
        int $stockBelow,
        int $addQuantity,
        bool $enabled,
    ): CartStockAutomationRuleResponse {
        $catalogItem = $this->entityManager->find(CatalogItem::class, $catalogItemId);
        if (!$catalogItem instanceof CatalogItem) {
            throw new NotFoundHttpException('Catalog item not found.');
        }
        $cart = $this->shoppingCartRepo->find($shoppingCartId);
        if (!$cart instanceof ShoppingCart) {
            throw new NotFoundHttpException('Shopping cart not found.');
        }
        $rule = new CartStockAutomationRule();
        $rule->changeCatalogItem($catalogItem);
        $rule->changeShoppingCart($cart);
        $rule->changeStockBelow($stockBelow);
        $rule->changeAddQuantity($addQuantity);
        $rule->changeEnabled($enabled);
        $this->automationRuleRepo->save($rule);

        return $this->map($rule);
    }

    public function patchRule(CatalogItemId $catalogItemId, CartStockAutomationRuleId $ruleId, PatchCartStockAutomationRuleRequest $patch): void
    {
        $rule = $this->mustFindRule($ruleId, $catalogItemId);
        $this->applyShoppingCartPatch($rule, $patch);
        $this->applyStockBelowPatch($rule, $patch);
        $this->applyAddQuantityPatch($rule, $patch);
        $this->applyEnabledPatch($rule, $patch);
        $this->automationRuleRepo->save($rule);
    }

    public function deleteRule(CatalogItemId $catalogItemId, CartStockAutomationRuleId $ruleId): void
    {
        $rule = $this->mustFindRule($ruleId, $catalogItemId);
        $this->automationRuleRepo->remove($rule);
    }

    private function mustFindRule(CartStockAutomationRuleId $ruleId, CatalogItemId $catalogItemId): CartStockAutomationRule
    {
        $rule = $this->automationRuleRepo->findOneByIdAndCatalogItemId($ruleId, $catalogItemId);
        if (!$rule instanceof CartStockAutomationRule) {
            throw new NotFoundHttpException('Automation rule not found.');
        }

        return $rule;
    }

    private function applyShoppingCartPatch(CartStockAutomationRule $rule, PatchCartStockAutomationRuleRequest $patch): void
    {
        if (!$patch->cartInPatch) {
            return;
        }
        $rule->changeShoppingCart($this->mustFindShoppingCartForPatch($patch));
    }

    private function mustFindShoppingCartForPatch(PatchCartStockAutomationRuleRequest $patch): ShoppingCart
    {
        $cartIri = $patch->cartIri;
        if (!\is_string($cartIri) || !str_starts_with($cartIri, '/api/shopping_carts/')) {
            throw new InvalidArgumentException('Invalid shopping cart IRI.');
        }
        $uuid = substr($cartIri, \strlen('/api/shopping_carts/'));
        $cart = $this->shoppingCartRepo->find(ShoppingCartId::fromString($uuid));
        if (!$cart instanceof ShoppingCart) {
            throw new NotFoundHttpException('Shopping cart not found.');
        }

        return $cart;
    }

    private function applyStockBelowPatch(CartStockAutomationRule $rule, PatchCartStockAutomationRuleRequest $patch): void
    {
        if (!$patch->stockBelowSpecified) {
            return;
        }
        $rule->changeStockBelow(self::requireIntValue($patch->stockBelow));
    }

    private function applyAddQuantityPatch(CartStockAutomationRule $rule, PatchCartStockAutomationRuleRequest $patch): void
    {
        if (!$patch->addQuantitySpecified) {
            return;
        }
        $rule->changeAddQuantity(self::requireIntValue($patch->addQuantity));
    }

    private function applyEnabledPatch(CartStockAutomationRule $rule, PatchCartStockAutomationRuleRequest $patch): void
    {
        if (!$patch->enabledSpecified) {
            return;
        }
        $rule->changeEnabled(self::requireBoolValue($patch->enabled));
    }

    private static function requireIntValue(mixed $raw): int
    {
        if (\is_int($raw)) {
            return $raw;
        }
        if (\is_string($raw) && is_numeric($raw)) {
            return (int) $raw;
        }
        throw new BadRequestHttpException('Expected an integer value.');
    }

    private static function requireBoolValue(mixed $raw): bool
    {
        if (\is_bool($raw)) {
            return $raw;
        }
        throw new BadRequestHttpException('enabled must be a boolean.');
    }

    private function map(CartStockAutomationRule $rule): CartStockAutomationRuleResponse
    {
        $catalogItem = $rule->getCatalogItem();
        $cart = $rule->getShoppingCart();
        if (null === $catalogItem || null === $cart) {
            throw new LogicException('Incomplete automation rule.');
        }

        return new CartStockAutomationRuleResponse(
            (string) $rule->getId(),
            ApiIri::catalogItem((string) $catalogItem->getId()),
            ApiIri::shoppingCart((string) $cart->getId()),
            $rule->getStockBelow(),
            $rule->getAddQuantity(),
            $rule->isEnabled(),
            $rule->getCreatedAt()->format(DateTimeInterface::ATOM),
        );
    }
}

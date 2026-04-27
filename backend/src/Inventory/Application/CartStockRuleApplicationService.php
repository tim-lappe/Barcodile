<?php

declare(strict_types=1);

namespace App\Inventory\Application;

use App\Cart\Application\ShoppingCartApplicationService;
use App\Catalog\Application\CatalogItemApplicationService;
use App\Inventory\Application\Dto\CartStockAutomationRuleResponse;
use App\Inventory\Application\Dto\PatchCartStockAutomationRuleRequest;
use App\Inventory\Domain\Entity\CartStockAutomationRule;
use App\Inventory\Domain\Repository\CartStockAutomationRuleRepository;
use App\SharedKernel\Application\ApiIri;
use App\SharedKernel\Domain\Id\CartStockAutomationRuleId;
use App\SharedKernel\Domain\Id\CatalogItemId;
use App\SharedKernel\Domain\Id\ShoppingCartId;
use App\SharedKernel\Domain\Math\BcQuantity;
use DateTimeInterface;
use InvalidArgumentException;
use LogicException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class CartStockRuleApplicationService
{
    private const int BC_SCALE = 4;

    public function __construct(
        private CartStockAutomationRuleRepository $automationRuleRepo,
        private CatalogItemApplicationService $catalog,
        private ShoppingCartApplicationService $cart,
    ) {
    }

    public function onAggregateTotalsChanged(string $catalogItemId, string $previousTotal, string $newTotal): void
    {
        $rules = $this->automationRuleRepo->findEnabledByCatalogItemId(CatalogItemId::fromString($catalogItemId));
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

    /**
     * @return list<CartStockAutomationRuleResponse>
     */
    public function listRules(string $catalogItemId): array
    {
        return array_map(
            fn (CartStockAutomationRuleView $rule): CartStockAutomationRuleResponse => $this->mapResponse($rule),
            array_map(
                fn (CartStockAutomationRule $rule): CartStockAutomationRuleView => $this->mapRule($rule),
                $this->automationRuleRepo->findAllByCatalogItemIdOrdered(CatalogItemId::fromString($catalogItemId)),
            ),
        );
    }

    public function createRule(
        string $catalogItemId,
        string $shoppingCartId,
        int $stockBelow,
        int $addQuantity,
        bool $enabled,
    ): CartStockAutomationRuleResponse {
        $this->catalog->ensureCatalogItemExists($catalogItemId);
        $this->cart->getShoppingCart($shoppingCartId);
        $rule = new CartStockAutomationRule();
        $rule->changeCatalogItemId(CatalogItemId::fromString($catalogItemId));
        $rule->changeShoppingCartId(ShoppingCartId::fromString($shoppingCartId));
        $rule->changeStockBelow($stockBelow);
        $rule->changeAddQuantity($addQuantity);
        $rule->changeEnabled($enabled);
        $this->automationRuleRepo->save($rule);

        return $this->mapResponse($this->mapRule($rule));
    }

    /**
     * @SuppressWarnings("PHPMD.ExcessiveParameterList")
     * @SuppressWarnings("PHPMD.CyclomaticComplexity")
     */
    public function patchRule(string $catalogItemId, string $ruleId, PatchCartStockAutomationRuleRequest $patch): void
    {
        $rule = $this->mustFindRule($ruleId, $catalogItemId);
        if ($patch->cartInPatch) {
            $rule->changeShoppingCartId($this->mustResolveShoppingCartId($patch->cartIri));
        }
        if ($patch->stockBelowSpecified) {
            $rule->changeStockBelow(self::requireIntValue($patch->stockBelow));
        }
        if ($patch->addQuantitySpecified) {
            $rule->changeAddQuantity(self::requireIntValue($patch->addQuantity));
        }
        if ($patch->enabledSpecified) {
            $rule->changeEnabled(self::requireBoolValue($patch->enabled));
        }
        $this->automationRuleRepo->save($rule);
    }

    public function deleteRule(string $catalogItemId, string $ruleId): void
    {
        $this->automationRuleRepo->remove($this->mustFindRule($ruleId, $catalogItemId));
    }

    private function enqueueAddition(CartStockAutomationRule $rule): void
    {
        $shoppingCartId = $rule->getShoppingCartId();
        $catalogItemId = $rule->getCatalogItemId();
        if (null === $shoppingCartId || null === $catalogItemId) {
            return;
        }
        $this->cart->createShoppingCartLine((string) $shoppingCartId, (string) $catalogItemId, $rule->getAddQuantity());
    }

    private function mustFindRule(string $ruleId, string $catalogItemId): CartStockAutomationRule
    {
        $rule = $this->automationRuleRepo->findOneByIdAndCatalogItemId(
            CartStockAutomationRuleId::fromString($ruleId),
            CatalogItemId::fromString($catalogItemId),
        );
        if (!$rule instanceof CartStockAutomationRule) {
            throw new NotFoundHttpException('Automation rule not found.');
        }

        return $rule;
    }

    private function mustResolveShoppingCartId(mixed $cartIri): ShoppingCartId
    {
        if (!\is_string($cartIri) || !str_starts_with($cartIri, '/api/shopping_carts/')) {
            throw new InvalidArgumentException('Invalid shopping cart IRI.');
        }
        $uuid = substr($cartIri, \strlen('/api/shopping_carts/'));
        $this->cart->getShoppingCart($uuid);

        return ShoppingCartId::fromString($uuid);
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

    private function mapRule(CartStockAutomationRule $rule): CartStockAutomationRuleView
    {
        $catalogItemId = $rule->getCatalogItemId();
        $cartId = $rule->getShoppingCartId();
        if (null === $catalogItemId || null === $cartId) {
            throw new LogicException('Incomplete automation rule.');
        }

        return new CartStockAutomationRuleView(
            (string) $rule->getId(),
            (string) $catalogItemId,
            (string) $cartId,
            $rule->getStockBelow(),
            $rule->getAddQuantity(),
            $rule->isEnabled(),
            $rule->getCreatedAt()->format(DateTimeInterface::ATOM),
        );
    }

    private function mapResponse(CartStockAutomationRuleView $rule): CartStockAutomationRuleResponse
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

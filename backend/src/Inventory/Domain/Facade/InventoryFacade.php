<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Facade;

use App\Cart\Domain\Facade\CartFacade;
use App\Catalog\Domain\Facade\CatalogFacade;
use App\Inventory\Domain\Entity\CartStockAutomationRule;
use App\Inventory\Domain\Entity\InventoryItem;
use App\Inventory\Domain\Entity\Location;
use App\Inventory\Domain\Repository\CartStockAutomationRuleRepository;
use App\Inventory\Domain\Repository\InventoryItemRepository;
use App\Inventory\Domain\Repository\LocationRepository;
use App\Printer\Domain\Facade\PrinterDeviceFacade;
use App\SharedKernel\Domain\Id\CartStockAutomationRuleId;
use App\SharedKernel\Domain\Id\CatalogItemId;
use App\SharedKernel\Domain\Id\InventoryItemId;
use App\SharedKernel\Domain\Id\LocationId;
use App\SharedKernel\Domain\Id\ShoppingCartId;
use App\SharedKernel\Domain\Math\BcQuantity;
use DateTimeInterface;
use InvalidArgumentException;
use LogicException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @SuppressWarnings("PHPMD.TooManyPublicMethods")
 */
final readonly class InventoryFacade
{
    private const int BC_SCALE = 4;

    public function __construct(
        private InventoryItemRepository $inventoryItemRepo,
        private CartStockAutomationRuleRepository $automationRuleRepo,
        private LocationRepository $locationRepository,
        private CatalogFacade $catalog,
        private CartFacade $cart,
        private InventoryLabelImageGenerator $labelImageGenerator,
        private PrinterDeviceFacade $printerDevices,
    ) {
    }

    /**
     * @return list<InventoryItemView>
     */
    public function listInventoryItems(): array
    {
        return array_map(fn (InventoryItem $item): InventoryItemView => $this->mapInventoryItem($item), $this->inventoryItemRepo->findAllOrderedById());
    }

    public function getInventoryItem(string $inventoryItemId): InventoryItemView
    {
        return $this->mapInventoryItem($this->findInventoryItem($inventoryItemId));
    }

    public function getInventoryItemLabelImage(string $inventoryItemId): string
    {
        return $this->labelImageGenerator->generate($this->findInventoryItem($inventoryItemId)->getPublicCode());
    }

    public function printInventoryItemLabel(string $inventoryItemId, string $printerDeviceId): void
    {
        $this->printerDevices->printLabelImage($printerDeviceId, $this->getInventoryItemLabelImage($inventoryItemId));
    }

    public function createInventoryItem(string $catalogItemId, ?string $locationId, ?DateTimeInterface $expirationDate): void
    {
        $this->catalog->getCatalogItem($catalogItemId);
        $item = new InventoryItem();
        $item->assignPublicCode($this->inventoryItemRepo->allocateNextPublicCode());
        $item->changeCatalogItemId(CatalogItemId::fromString($catalogItemId));
        $item->changeLocation(null === $locationId ? null : $this->locationRepositoryFind(LocationId::fromString($locationId)));
        $item->changeExpirationDate($expirationDate);
        $this->inventoryItemRepo->save($item);
    }

    public function updateInventoryItem(string $inventoryItemId, string $catalogItemId, ?string $locationId, ?DateTimeInterface $expirationDate): void
    {
        $this->catalog->getCatalogItem($catalogItemId);
        $item = $this->findInventoryItem($inventoryItemId);
        $item->changeCatalogItemId(CatalogItemId::fromString($catalogItemId));
        $item->changeLocation(null === $locationId ? null : $this->locationRepositoryFind(LocationId::fromString($locationId)));
        $item->changeExpirationDate($expirationDate);
        $this->inventoryItemRepo->save($item);
    }

    public function deleteInventoryItem(string $inventoryItemId): void
    {
        $this->inventoryItemRepo->remove($this->findInventoryItem($inventoryItemId));
    }

    /**
     * @return list<CartStockAutomationRuleView>
     */
    public function listCartStockRules(string $catalogItemId): array
    {
        return array_map(
            fn (CartStockAutomationRule $rule): CartStockAutomationRuleView => $this->mapRule($rule),
            $this->automationRuleRepo->findAllByCatalogItemIdOrdered(CatalogItemId::fromString($catalogItemId)),
        );
    }

    public function createCartStockRule(
        string $catalogItemId,
        string $shoppingCartId,
        int $stockBelow,
        int $addQuantity,
        bool $enabled,
    ): CartStockAutomationRuleView {
        $this->catalog->getCatalogItem($catalogItemId);
        $this->cart->getShoppingCart($shoppingCartId);
        $rule = new CartStockAutomationRule();
        $rule->changeCatalogItemId(CatalogItemId::fromString($catalogItemId));
        $rule->changeShoppingCartId(ShoppingCartId::fromString($shoppingCartId));
        $rule->changeStockBelow($stockBelow);
        $rule->changeAddQuantity($addQuantity);
        $rule->changeEnabled($enabled);
        $this->automationRuleRepo->save($rule);

        return $this->mapRule($rule);
    }

    /**
     * @SuppressWarnings("PHPMD.ExcessiveParameterList")
     * @SuppressWarnings("PHPMD.CyclomaticComplexity")
     */
    public function patchCartStockRule(
        string $catalogItemId,
        string $ruleId,
        bool $cartInPatch,
        mixed $cartIri,
        bool $stockBelowSpecified,
        mixed $stockBelow,
        bool $addQuantitySpecified,
        mixed $addQuantity,
        bool $enabledSpecified,
        mixed $enabled,
    ): void {
        $rule = $this->mustFindRule($ruleId, $catalogItemId);
        if ($cartInPatch) {
            $rule->changeShoppingCartId($this->mustResolveShoppingCartId($cartIri));
        }
        if ($stockBelowSpecified) {
            $rule->changeStockBelow(self::requireIntValue($stockBelow));
        }
        if ($addQuantitySpecified) {
            $rule->changeAddQuantity(self::requireIntValue($addQuantity));
        }
        if ($enabledSpecified) {
            $rule->changeEnabled(self::requireBoolValue($enabled));
        }
        $this->automationRuleRepo->save($rule);
    }

    public function deleteCartStockRule(string $catalogItemId, string $ruleId): void
    {
        $this->automationRuleRepo->remove($this->mustFindRule($ruleId, $catalogItemId));
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

    private function enqueueAddition(CartStockAutomationRule $rule): void
    {
        $shoppingCartId = $rule->getShoppingCartId();
        $catalogItemId = $rule->getCatalogItemId();
        if (null === $shoppingCartId || null === $catalogItemId) {
            return;
        }
        $this->cart->createShoppingCartLine((string) $shoppingCartId, (string) $catalogItemId, $rule->getAddQuantity());
    }

    private function findInventoryItem(string $inventoryItemId): InventoryItem
    {
        $item = $this->inventoryItemRepo->find(InventoryItemId::fromString($inventoryItemId));
        if (!$item instanceof InventoryItem) {
            throw new NotFoundHttpException('Inventory item not found.');
        }

        return $item;
    }

    private function locationRepositoryFind(LocationId $locationId): Location
    {
        $location = $this->locationRepository->find($locationId);
        if (!$location instanceof Location) {
            throw new NotFoundHttpException('Location not found.');
        }

        return $location;
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

    private function mapInventoryItem(InventoryItem $item): InventoryItemView
    {
        $catalogItemId = $item->getCatalogItemId();
        if (null === $catalogItemId) {
            throw new LogicException('Inventory item without catalog item.');
        }
        $location = $item->getLocation();
        $exp = $item->getExpirationDate();

        return new InventoryItemView(
            (string) $item->getId(),
            $item->getPublicCode(),
            $this->catalog->getCatalogItem((string) $catalogItemId),
            null === $location ? null : $this->mapLocation($location),
            null === $exp ? null : $exp->format(DateTimeInterface::ATOM),
            $item->getCreatedAt()->format(DateTimeInterface::ATOM),
        );
    }

    private function mapLocation(Location $location): LocationView
    {
        $parent = $location->getParent();

        return new LocationView(
            (string) $location->getId(),
            $location->getName(),
            null === $parent ? null : (string) $parent->getId(),
        );
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
}

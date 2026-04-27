<?php

declare(strict_types=1);

namespace App\Domain\Inventory\Entity;

use App\Domain\Inventory\Repository\CartStockAutomationRuleRepository;
use App\Domain\Shared\Id\CartStockAutomationRuleId;
use App\Domain\Shared\Id\CatalogItemId;
use App\Domain\Shared\Id\ShoppingCartId;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CartStockAutomationRuleRepository::class)]
#[ORM\Table(name: 'cart_stock_automation_rule')]
#[ORM\UniqueConstraint(name: 'uniq_cart_automation_catalog_cart', columns: ['catalog_item_id', 'shopping_cart_id'])]
class CartStockAutomationRule
{
    #[ORM\Id]
    #[ORM\Column(type: 'cart_stock_automation_rule_id', unique: true)]
    private CartStockAutomationRuleId $ruleId;

    #[ORM\Column(name: 'catalog_item_id', type: 'catalog_item_id')]
    private ?CatalogItemId $catalogItemId = null;

    #[ORM\Column(name: 'shopping_cart_id', type: 'shopping_cart_id')]
    private ?ShoppingCartId $shoppingCartId = null;

    #[ORM\Column]
    private int $stockBelow = 0;

    #[ORM\Column]
    private int $addQuantity = 1;

    #[ORM\Column(options: ['default' => true])]
    private bool $enabled = true;

    #[ORM\Column]
    private DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->ruleId = new CartStockAutomationRuleId();
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): CartStockAutomationRuleId
    {
        return $this->ruleId;
    }

    public function getCatalogItemId(): ?CatalogItemId
    {
        return $this->catalogItemId;
    }

    public function changeCatalogItemId(?CatalogItemId $catalogItemId): static
    {
        $this->catalogItemId = $catalogItemId;

        return $this;
    }

    public function getShoppingCartId(): ?ShoppingCartId
    {
        return $this->shoppingCartId;
    }

    public function changeShoppingCartId(?ShoppingCartId $shoppingCartId): static
    {
        $this->shoppingCartId = $shoppingCartId;

        return $this;
    }

    public function getStockBelow(): int
    {
        return $this->stockBelow;
    }

    public function changeStockBelow(int $stockBelow): static
    {
        $this->stockBelow = $stockBelow;

        return $this;
    }

    public function getAddQuantity(): int
    {
        return $this->addQuantity;
    }

    public function changeAddQuantity(int $addQuantity): static
    {
        $this->addQuantity = $addQuantity;

        return $this;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function changeEnabled(bool $enabled): static
    {
        $this->enabled = $enabled;

        return $this;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}

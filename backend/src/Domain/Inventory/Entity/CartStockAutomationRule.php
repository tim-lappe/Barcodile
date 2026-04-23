<?php

declare(strict_types=1);

namespace App\Domain\Inventory\Entity;

use App\Domain\Cart\Entity\ShoppingCart;
use App\Domain\Catalog\Entity\CatalogItem;
use App\Domain\Inventory\Repository\CartStockAutomationRuleRepository;
use App\Domain\Shared\Id\CartStockAutomationRuleId;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CartStockAutomationRuleRepository::class)]
#[ORM\Table(name: 'cart_stock_automation_rule')]
#[ORM\UniqueConstraint(name: 'uniq_cart_automation_catalog_cart', columns: ['catalog_item_id', 'shopping_cart_id'])]
class CartStockAutomationRule
{
    #[ORM\Id]
    #[ORM\Column(type: 'cart_stock_automation_rule_id', unique: true)]
    private CartStockAutomationRuleId $ruleId;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'catalog_item_id', referencedColumnName: 'catalog_item_id', nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    private ?CatalogItem $catalogItem = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'shopping_cart_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    private ?ShoppingCart $shoppingCart = null;

    #[ORM\Column]
    #[Assert\PositiveOrZero]
    private int $stockBelow = 0;

    #[ORM\Column]
    #[Assert\Positive]
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

    public function getCatalogItem(): ?CatalogItem
    {
        return $this->catalogItem;
    }

    public function changeCatalogItem(?CatalogItem $catalogItem): static
    {
        $this->catalogItem = $catalogItem;

        return $this;
    }

    public function getShoppingCart(): ?ShoppingCart
    {
        return $this->shoppingCart;
    }

    public function changeShoppingCart(?ShoppingCart $shoppingCart): static
    {
        $this->shoppingCart = $shoppingCart;

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

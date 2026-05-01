<?php

declare(strict_types=1);

namespace App\Cart\Domain\Entity;

use App\Cart\Domain\Exception\InvalidCartException;
use App\SharedKernel\Domain\Id\CatalogItemId;
use App\SharedKernel\Domain\Id\ShoppingCartLineId;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'shopping_cart_line')]
class ShoppingCartLine
{
    #[ORM\Id]
    #[ORM\Column(name: 'id', type: 'shopping_cart_line_id', unique: true)]
    private ShoppingCartLineId $shoppingCartLineId;

    #[ORM\ManyToOne(inversedBy: 'lines')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?ShoppingCart $shoppingCart = null;

    #[ORM\Column(name: 'item_type_id', type: 'catalog_item_id')]
    private ?CatalogItemId $catalogItemId = null;

    #[ORM\Column]
    private int $quantity = 1;

    #[ORM\Column]
    private DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->shoppingCartLineId = new ShoppingCartLineId();
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): ShoppingCartLineId
    {
        return $this->shoppingCartLineId;
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

    public function getCatalogItemId(): ?CatalogItemId
    {
        return $this->catalogItemId;
    }

    public function changeCatalogItemId(?CatalogItemId $catalogItemId): static
    {
        $this->catalogItemId = $catalogItemId;

        return $this;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function changeQuantity(int $quantity): static
    {
        $this->assertQuantity($quantity);
        $this->quantity = $quantity;

        return $this;
    }

    public function increaseQuantity(int $delta): static
    {
        $this->assertQuantity($delta);
        $this->quantity += $delta;

        return $this;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    private function assertQuantity(int $quantity): void
    {
        if ($quantity < 1) {
            throw new InvalidCartException('Quantity must be at least 1.');
        }
    }
}

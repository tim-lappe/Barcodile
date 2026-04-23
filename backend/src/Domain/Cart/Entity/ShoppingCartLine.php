<?php

declare(strict_types=1);

namespace App\Domain\Cart\Entity;

use App\Domain\Catalog\Entity\CatalogItem;
use App\Domain\Shared\Id\ShoppingCartLineId;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'shopping_cart_line')]
class ShoppingCartLine
{
    #[Groups(['shopping_cart_line:read', 'shopping_cart:read'])]
    #[ORM\Id]
    #[ORM\Column(name: 'id', type: 'shopping_cart_line_id', unique: true)]
    private ShoppingCartLineId $shoppingCartLineId;

    #[Groups(['shopping_cart_line:write'])]
    #[ORM\ManyToOne(inversedBy: 'lines')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    private ?ShoppingCart $shoppingCart = null;

    #[Groups(['shopping_cart_line:read', 'shopping_cart_line:write', 'shopping_cart:read'])]
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'item_type_id', referencedColumnName: 'catalog_item_id', nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    private ?CatalogItem $catalogItem = null;

    #[Groups(['shopping_cart_line:read', 'shopping_cart_line:write', 'shopping_cart:read'])]
    #[ORM\Column]
    #[Assert\Positive]
    private int $quantity = 1;

    #[Groups(['shopping_cart_line:read', 'shopping_cart:read'])]
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

    public function getCatalogItem(): ?CatalogItem
    {
        return $this->catalogItem;
    }

    public function changeCatalogItem(?CatalogItem $catalogItem): static
    {
        $this->catalogItem = $catalogItem;

        return $this;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function changeQuantity(int $quantity): static
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function increaseQuantity(int $delta): static
    {
        $this->quantity += $delta;

        return $this;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}

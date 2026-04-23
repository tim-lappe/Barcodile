<?php

declare(strict_types=1);

namespace App\Domain\Cart\Entity;

use App\Domain\Cart\Repository\ShoppingCartRepository;
use App\Domain\Catalog\Entity\CatalogItem;
use App\Domain\Shared\Id\ShoppingCartId;
use App\Domain\Shared\Id\ShoppingCartLineId;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ShoppingCartRepository::class)]
#[ORM\Table(name: 'shopping_cart')]
class ShoppingCart
{
    #[Groups(['shopping_cart:read'])]
    #[ORM\Id]
    #[ORM\Column(name: 'id', type: 'shopping_cart_id', unique: true)]
    private ShoppingCartId $shoppingCartId;

    #[Groups(['shopping_cart:read', 'shopping_cart:create', 'shopping_cart:patch'])]
    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    private ?string $name = null;

    #[Groups(['shopping_cart:read'])]
    #[ORM\Column]
    private DateTimeImmutable $createdAt;

    /**
     * @var Collection<int, ShoppingCartLine>
     */
    #[Groups(['shopping_cart:read'])]
    #[ORM\OneToMany(targetEntity: ShoppingCartLine::class, mappedBy: 'shoppingCart', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['createdAt' => 'ASC'])]
    private Collection $lines;

    public function __construct()
    {
        $this->shoppingCartId = new ShoppingCartId();
        $this->createdAt = new DateTimeImmutable();
        $this->lines = new ArrayCollection();
    }

    public function getId(): ShoppingCartId
    {
        return $this->shoppingCartId;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function changeName(?string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * @return Collection<int, ShoppingCartLine>
     */
    public function getLines(): Collection
    {
        return $this->lines;
    }

    public function addLine(ShoppingCartLine $line): static
    {
        if (!$this->lines->contains($line)) {
            $this->lines->add($line);
            $line->changeShoppingCart($this);
        }

        return $this;
    }

    public function removeLine(ShoppingCartLine $line): static
    {
        $this->lines->removeElement($line);

        return $this;
    }

    public function mergeOrAddLineForCatalogItem(CatalogItem $catalogItem, int $quantity): ShoppingCartLine
    {
        $catalogItemId = $catalogItem->getId();
        foreach ($this->lines as $existing) {
            $existingItem = $existing->getCatalogItem();
            if (null !== $existingItem && $existingItem->getId()->equals($catalogItemId)) {
                $existing->increaseQuantity($quantity);

                return $existing;
            }
        }
        $line = new ShoppingCartLine();
        $line->changeShoppingCart($this);
        $line->changeCatalogItem($catalogItem);
        $line->changeQuantity($quantity);
        $this->addLine($line);

        return $line;
    }

    public function detachLineById(ShoppingCartLineId $lineId): void
    {
        foreach ($this->lines as $line) {
            if ($line->getId()->equals($lineId)) {
                $this->removeLine($line);

                return;
            }
        }
    }

    public function applyLineQuantityByLineId(ShoppingCartLineId $lineId, int $quantity): void
    {
        foreach ($this->lines as $line) {
            if ($line->getId()->equals($lineId)) {
                $line->changeQuantity($quantity);

                return;
            }
        }
    }
}

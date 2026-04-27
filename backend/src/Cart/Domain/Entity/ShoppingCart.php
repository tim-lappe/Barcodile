<?php

declare(strict_types=1);

namespace App\Cart\Domain\Entity;

use App\Cart\Domain\Repository\ShoppingCartRepository;
use App\SharedKernel\Domain\Id\CatalogItemId;
use App\SharedKernel\Domain\Id\ShoppingCartId;
use App\SharedKernel\Domain\Id\ShoppingCartLineId;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ShoppingCartRepository::class)]
#[ORM\Table(name: 'shopping_cart')]
class ShoppingCart
{
    #[ORM\Id]
    #[ORM\Column(name: 'id', type: 'shopping_cart_id', unique: true)]
    private ShoppingCartId $shoppingCartId;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $name = null;

    #[ORM\Column]
    private DateTimeImmutable $createdAt;

    /**
     * @var Collection<int, ShoppingCartLine>
     */
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

    public function mergeOrAddLineForCatalogItem(CatalogItemId $catalogItemId, int $quantity): ShoppingCartLine
    {
        foreach ($this->lines as $existing) {
            $existingItemId = $existing->getCatalogItemId();
            if (null !== $existingItemId && $existingItemId->equals($catalogItemId)) {
                $existing->increaseQuantity($quantity);

                return $existing;
            }
        }
        $line = new ShoppingCartLine();
        $line->changeShoppingCart($this);
        $line->changeCatalogItemId($catalogItemId);
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

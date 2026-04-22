<?php

declare(strict_types=1);

namespace App\Domain\Inventory\Entity;

use App\Domain\Catalog\Entity\CatalogItem;
use App\Domain\Inventory\Events\InventoryItemCatalogItemChanged;
use App\Domain\Inventory\Events\InventoryItemCreated;
use App\Domain\Inventory\Events\InventoryItemDeleted;
use App\Domain\Inventory\Events\InventoryItemExpirationDateChanged;
use App\Domain\Inventory\Events\InventoryItemLocationChanged;
use App\Domain\Inventory\Repository\InventoryItemRepository;
use App\Domain\Shared\DomainEventRecorder;
use App\Domain\Shared\RecordsDomainEvents;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use LogicException;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: InventoryItemRepository::class)]
#[ORM\Table(name: 'inventory_item')]
#[ORM\HasLifecycleCallbacks]
class InventoryItem implements RecordsDomainEvents
{
    use DomainEventRecorder;

    #[Groups(['inventory_item:read'])]
    #[ORM\Id]
    #[ORM\Column(type: 'inventory_item_id', unique: true)]
    private InventoryItemId $inventoryItemId;

    #[Groups(['inventory_item:read'])]
    #[ORM\Column(length: 32, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Regex(pattern: '/^\d+$/')]
    private string $publicCode = '';

    #[Groups(['inventory_item:read', 'inventory_item:write'])]
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'item_type_id', referencedColumnName: 'catalog_item_id', nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    private ?CatalogItem $catalogItem = null;

    #[Groups(['inventory_item:read', 'inventory_item:write'])]
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(referencedColumnName: 'location_id', onDelete: 'SET NULL')]
    private ?Location $location = null;

    #[Groups(['inventory_item:read', 'inventory_item:write'])]
    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?DateTimeInterface $expirationDate = null;

    #[Groups(['inventory_item:read'])]
    #[ORM\Column]
    private DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->inventoryItemId = new InventoryItemId();
        $this->createdAt = new DateTimeImmutable();
        $this->recordDomainEvent(new InventoryItemCreated($this));
    }

    public function assignPublicCode(string $publicCode): void
    {
        if ('' !== $this->publicCode) {
            throw new LogicException('Public code already assigned.');
        }
        if (!preg_match('/^\d+$/', $publicCode)) {
            throw new LogicException('Public code must contain digits only.');
        }
        $this->publicCode = $publicCode;
    }

    public function getId(): InventoryItemId
    {
        return $this->inventoryItemId;
    }

    public function getPublicCode(): string
    {
        return $this->publicCode;
    }

    public function getCatalogItem(): ?CatalogItem
    {
        return $this->catalogItem;
    }

    public function changeCatalogItem(?CatalogItem $catalogItem): static
    {
        if ($this->catalogItem === $catalogItem) {
            return $this;
        }
        $previous = $this->catalogItem;
        $this->catalogItem = $catalogItem;
        $this->recordDomainEvent(new InventoryItemCatalogItemChanged($this, $previous, $catalogItem));

        return $this;
    }

    public function getLocation(): ?Location
    {
        return $this->location;
    }

    public function changeLocation(?Location $location): static
    {
        if ($this->location === $location) {
            return $this;
        }
        $previous = $this->location;
        $this->location = $location;
        $this->recordDomainEvent(new InventoryItemLocationChanged($this, $previous, $location));

        return $this;
    }

    public function getExpirationDate(): ?DateTimeInterface
    {
        return $this->expirationDate;
    }

    public function changeExpirationDate(?DateTimeInterface $expirationDate): static
    {
        if ($this->sameExpiration($this->expirationDate, $expirationDate)) {
            return $this;
        }
        $previous = $this->expirationDate;
        $this->expirationDate = $expirationDate;
        $this->recordDomainEvent(new InventoryItemExpirationDateChanged($this, $previous, $expirationDate));

        return $this;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    #[ORM\PreRemove]
    public function recordDeletionEvent(): void
    {
        $catalogId = $this->catalogItem?->getId();
        if (null !== $catalogId) {
            $this->recordDomainEvent(new InventoryItemDeleted($this->inventoryItemId, $catalogId));
        }
    }

    private function sameExpiration(?DateTimeInterface $first, ?DateTimeInterface $second): bool
    {
        if (null === $first) {
            return null === $second;
        }
        if (null === $second) {
            return false;
        }

        return $first->format('Y-m-d H:i:s.u') === $second->format('Y-m-d H:i:s.u');
    }
}

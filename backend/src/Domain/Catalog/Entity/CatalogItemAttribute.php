<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Entity;

use App\Domain\Shared\CatalogItemAttributeKey;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(
    name: 'item_type_attribute',
    uniqueConstraints: [
        new ORM\UniqueConstraint(name: 'uniq_item_type_attribute', columns: ['item_type_id', 'item_attribute']),
    ],
)]
class CatalogItemAttribute
{
    #[Groups(['catalog_item_attribute:read', 'catalog_item:read', 'inventory_item:read'])]
    #[ORM\Id]
    #[ORM\Column(type: 'catalog_item_attribute_id', unique: true)]
    private CatalogItemAttributeId $attributeId;

    #[Groups(['catalog_item_attribute:read', 'catalog_item_attribute:write'])]
    #[ORM\ManyToOne(inversedBy: 'itemAttributes')]
    #[ORM\JoinColumn(name: 'item_type_id', referencedColumnName: 'catalog_item_id', nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    private ?CatalogItem $catalogItem = null;

    #[Groups(['catalog_item_attribute:read', 'catalog_item_attribute:write', 'catalog_item:read', 'inventory_item:read'])]
    #[ORM\Column(name: 'item_attribute', length: 32, enumType: CatalogItemAttributeKey::class)]
    #[Assert\NotNull]
    private ?CatalogItemAttributeKey $attribute = null;

    #[Groups(['catalog_item_attribute:read', 'catalog_item_attribute:write', 'catalog_item:read', 'inventory_item:read'])]
    #[ORM\Column(type: 'json', nullable: true)]
    private mixed $value = null;

    public function __construct()
    {
        $this->attributeId = new CatalogItemAttributeId();
    }

    public function getId(): CatalogItemAttributeId
    {
        return $this->attributeId;
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

    public function getAttribute(): ?CatalogItemAttributeKey
    {
        return $this->attribute;
    }

    public function changeAttribute(?CatalogItemAttributeKey $attribute): static
    {
        $this->attribute = $attribute;

        return $this;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function changeValue(mixed $value): static
    {
        $this->value = $value;

        return $this;
    }
}

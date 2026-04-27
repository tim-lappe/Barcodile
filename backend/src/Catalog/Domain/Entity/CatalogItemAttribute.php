<?php

declare(strict_types=1);

namespace App\Catalog\Domain\Entity;

use App\SharedKernel\Domain\CatalogItemAttributeKey;
use App\SharedKernel\Domain\Id\CatalogItemAttributeId;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(
    name: 'item_type_attribute',
    uniqueConstraints: [
        new ORM\UniqueConstraint(name: 'uniq_item_type_attribute', columns: ['item_type_id', 'item_attribute']),
    ],
)]
class CatalogItemAttribute
{
    #[ORM\Id]
    #[ORM\Column(type: 'catalog_item_attribute_id', unique: true)]
    private CatalogItemAttributeId $attributeId;

    #[ORM\ManyToOne(inversedBy: 'itemAttributes')]
    #[ORM\JoinColumn(name: 'item_type_id', referencedColumnName: 'catalog_item_id', nullable: false, onDelete: 'CASCADE')]
    private ?CatalogItem $catalogItem = null;

    #[ORM\Column(name: 'item_attribute', length: 32, enumType: CatalogItemAttributeKey::class)]
    private ?CatalogItemAttributeKey $attribute = null;

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

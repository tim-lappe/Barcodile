<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Entity;

use App\Domain\Catalog\Entity\Embeddable\BarcodeEmbeddable;
use App\Domain\Catalog\Entity\Embeddable\VolumeEmbeddable;
use App\Domain\Catalog\Entity\Embeddable\WeightEmbeddable;
use App\Domain\Catalog\Repository\CatalogItemRepository;
use App\Domain\Shared\Barcode as BarcodeValue;
use App\Domain\Shared\Id\CatalogItemId;
use App\Domain\Shared\Volume;
use App\Domain\Shared\Weight;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CatalogItemRepository::class)]
#[ORM\Table(name: 'item_type')]
class CatalogItem
{
    #[Groups(['catalog_item:read', 'inventory_item:read', 'shopping_cart_line:read'])]
    #[ORM\Id]
    #[ORM\Column(type: 'catalog_item_id', unique: true)]
    private CatalogItemId $catalogItemId;

    #[Groups(['catalog_item:read', 'catalog_item:write', 'inventory_item:read', 'shopping_cart_line:read'])]
    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private string $name = '';

    #[Groups(['catalog_item:read', 'inventory_item:read', 'shopping_cart_line:read'])]
    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    private ?string $imageFileName = null;

    #[ORM\Embedded(class: VolumeEmbeddable::class, columnPrefix: 'volume_')]
    private VolumeEmbeddable $volumeEmbeddable;

    #[ORM\Embedded(class: WeightEmbeddable::class, columnPrefix: 'weight_')]
    private WeightEmbeddable $weightEmbeddable;

    #[ORM\Embedded(class: BarcodeEmbeddable::class, columnPrefix: 'barcode_')]
    private ?BarcodeEmbeddable $barcode = null;

    /**
     * @var Collection<int, CatalogItemAttribute>
     */
    #[Assert\Valid]
    #[Groups(['catalog_item:read', 'inventory_item:read'])]
    #[ORM\OneToMany(targetEntity: CatalogItemAttribute::class, mappedBy: 'catalogItem', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $itemAttributes;

    public function __construct()
    {
        $this->catalogItemId = new CatalogItemId();
        $this->volumeEmbeddable = new VolumeEmbeddable();
        $this->weightEmbeddable = new WeightEmbeddable();
        $this->itemAttributes = new ArrayCollection();
    }

    public function getId(): CatalogItemId
    {
        return $this->catalogItemId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function changeName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getImageFileName(): ?string
    {
        return $this->imageFileName;
    }

    public function changeImageFileName(?string $imageFileName): static
    {
        $this->imageFileName = $imageFileName;

        return $this;
    }

    #[Groups(['catalog_item:read', 'catalog_item:write', 'inventory_item:read'])]
    public function getVolume(): ?Volume
    {
        return $this->volumeEmbeddable->toValue();
    }

    public function changeVolume(?Volume $volume): static
    {
        $this->volumeEmbeddable->apply($volume);

        return $this;
    }

    #[Groups(['catalog_item:read', 'catalog_item:write', 'inventory_item:read'])]
    public function getWeight(): ?Weight
    {
        return $this->weightEmbeddable->toValue();
    }

    public function changeWeight(?Weight $weight): static
    {
        $this->weightEmbeddable->apply($weight);

        return $this;
    }

    #[Groups(['catalog_item:read', 'inventory_item:read', 'shopping_cart_line:read'])]
    public function getBarcode(): ?BarcodeValue
    {
        return $this->barcode?->toValue();
    }

    public function changeBarcode(?BarcodeValue $barcode): static
    {
        if (null === $barcode) {
            $this->barcode = null;

            return $this;
        }
        $code = trim($barcode->getCode());
        if ('' === $code) {
            $this->barcode = null;

            return $this;
        }
        $normalized = new BarcodeValue($code, $barcode->getType());
        if (null === $this->barcode) {
            $this->barcode = new BarcodeEmbeddable();
        }
        $this->barcode->apply($normalized);

        return $this;
    }

    /**
     * @return Collection<int, CatalogItemAttribute>
     */
    public function getCatalogItemAttributes(): Collection
    {
        return $this->itemAttributes;
    }

    public function addCatalogItemAttribute(CatalogItemAttribute $catalogItemAttribute): static
    {
        if (!$this->itemAttributes->contains($catalogItemAttribute)) {
            $this->itemAttributes->add($catalogItemAttribute);
            $catalogItemAttribute->changeCatalogItem($this);
        }

        return $this;
    }

    public function removeCatalogItemAttribute(CatalogItemAttribute $catalogItemAttribute): static
    {
        $this->itemAttributes->removeElement($catalogItemAttribute);

        return $this;
    }
}

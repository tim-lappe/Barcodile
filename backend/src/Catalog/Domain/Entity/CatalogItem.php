<?php

declare(strict_types=1);

namespace App\Catalog\Domain\Entity;

use App\Catalog\Domain\Entity\Embeddable\BarcodeEmbeddable;
use App\Catalog\Domain\Entity\Embeddable\VolumeEmbeddable;
use App\Catalog\Domain\Entity\Embeddable\WeightEmbeddable;
use App\Catalog\Domain\Image;
use App\Catalog\Domain\Repository\CatalogItemRepository;
use App\SharedKernel\Domain\Barcode as BarcodeValue;
use App\SharedKernel\Domain\Id\CatalogItemId;
use App\SharedKernel\Domain\Volume;
use App\SharedKernel\Domain\Weight;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CatalogItemRepository::class)]
#[ORM\Table(name: 'item_type')]
class CatalogItem
{
    #[ORM\Id]
    #[ORM\Column(type: 'catalog_item_id', unique: true)]
    private CatalogItemId $catalogItemId;

    #[ORM\Column(length: 255)]
    private string $name = '';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $imageFileName = null;

    #[ORM\OneToOne(targetEntity: CatalogItemImage::class, mappedBy: 'catalogItem', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private ?CatalogItemImage $catalogItemImage = null;

    #[ORM\Embedded(class: VolumeEmbeddable::class, columnPrefix: 'volume_')]
    private VolumeEmbeddable $volumeEmbeddable;

    #[ORM\Embedded(class: WeightEmbeddable::class, columnPrefix: 'weight_')]
    private WeightEmbeddable $weightEmbeddable;

    #[ORM\Embedded(class: BarcodeEmbeddable::class, columnPrefix: 'barcode_')]
    private ?BarcodeEmbeddable $barcode = null;

    /**
     * @var Collection<int, CatalogItemAttribute>
     */
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

    public function assignImage(Image $image): void
    {
        $this->imageFileName = $image->getFileName();
        if (null !== $this->catalogItemImage) {
            $this->catalogItemImage->rewrite($image);

            return;
        }
        $this->catalogItemImage = new CatalogItemImage($this, $image);
    }

    public function clearImage(): void
    {
        $this->imageFileName = null;
        $this->catalogItemImage = null;
    }

    public function getCatalogItemImage(): ?CatalogItemImage
    {
        return $this->catalogItemImage;
    }

    public function getImage(): ?Image
    {
        if (null === $this->imageFileName || '' === $this->imageFileName || null === $this->catalogItemImage) {
            return null;
        }

        return $this->catalogItemImage->toImage($this->imageFileName);
    }

    public function getVolume(): ?Volume
    {
        return $this->volumeEmbeddable->toValue();
    }

    public function changeVolume(?Volume $volume): static
    {
        $this->volumeEmbeddable->apply($volume);

        return $this;
    }

    public function getWeight(): ?Weight
    {
        return $this->weightEmbeddable->toValue();
    }

    public function changeWeight(?Weight $weight): static
    {
        $this->weightEmbeddable->apply($weight);

        return $this;
    }

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

<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Entity;

use App\Domain\Catalog\Entity\Embeddable\BarcodeEmbeddable;
use App\Domain\Shared\Barcode as BarcodeValue;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
class Barcode
{
    #[Groups(['barcode:read', 'catalog_item:read'])]
    #[ORM\Id]
    #[ORM\Column(name: 'id', type: 'barcode_id', unique: true)]
    private BarcodeId $barcodeId;

    #[ORM\Embedded(class: BarcodeEmbeddable::class, columnPrefix: false)]
    private BarcodeEmbeddable $barcode;

    #[Groups(['barcode:read', 'barcode:write'])]
    #[ORM\ManyToOne(inversedBy: 'barcodes')]
    #[ORM\JoinColumn(name: 'item_type_id', referencedColumnName: 'catalog_item_id', nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    private ?CatalogItem $catalogItem = null;

    public function __construct()
    {
        $this->barcodeId = new BarcodeId();
        $this->barcode = new BarcodeEmbeddable();
    }

    public function getId(): BarcodeId
    {
        return $this->barcodeId;
    }

    #[Groups(['barcode:read', 'barcode:write', 'catalog_item:read'])]
    public function getBarcode(): BarcodeValue
    {
        return $this->barcode->toValue();
    }

    public function changeBarcode(BarcodeValue $barcode): static
    {
        $this->barcode->apply($barcode);

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
}

<?php

declare(strict_types=1);

namespace App\Domain\Picnic\Entity;

use App\Domain\Catalog\Entity\CatalogItem;
use App\Domain\Picnic\Repository\PicnicCatalogItemProductLinkRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PicnicCatalogItemProductLinkRepository::class)]
#[ORM\Table(name: 'picnic_catalog_item_product_link')]
class PicnicCatalogItemProductLink
{
    #[ORM\Id]
    #[ORM\OneToOne(targetEntity: CatalogItem::class)]
    #[ORM\JoinColumn(name: 'catalog_item_id', referencedColumnName: 'catalog_item_id', onDelete: 'CASCADE')]
    private CatalogItem $catalogItem;

    #[ORM\Column(length: 255)]
    #[Assert\Length(max: 255)]
    private string $productId;

    public function __construct(CatalogItem $catalogItem, string $productId)
    {
        $this->catalogItem = $catalogItem;
        $this->changeProductId($productId);
    }

    public function getCatalogItem(): CatalogItem
    {
        return $this->catalogItem;
    }

    public function getProductId(): string
    {
        return $this->productId;
    }

    public function changeProductId(string $productId): static
    {
        $trimmed = trim($productId);
        $this->productId = $trimmed;

        return $this;
    }
}

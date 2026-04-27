<?php

declare(strict_types=1);

namespace App\Domain\Picnic\Entity;

use App\Domain\Picnic\Repository\PicnicCatalogItemProductLinkRepository;
use App\Domain\Shared\Id\CatalogItemId;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PicnicCatalogItemProductLinkRepository::class)]
#[ORM\Table(name: 'picnic_catalog_item_product_link')]
class PicnicCatalogItemProductLink
{
    #[ORM\Id]
    #[ORM\Column(name: 'catalog_item_id', type: 'catalog_item_id')]
    private CatalogItemId $catalogItemId;

    #[ORM\Column(length: 255)]
    private string $productId;

    public function __construct(CatalogItemId $catalogItemId, string $productId)
    {
        $this->catalogItemId = $catalogItemId;
        $this->changeProductId($productId);
    }

    public function getCatalogItemId(): CatalogItemId
    {
        return $this->catalogItemId;
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

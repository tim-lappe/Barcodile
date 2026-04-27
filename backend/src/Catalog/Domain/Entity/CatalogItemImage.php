<?php

declare(strict_types=1);

namespace App\Catalog\Domain\Entity;

use App\Catalog\Domain\Image;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'catalog_item_image')]
class CatalogItemImage
{
    #[ORM\Id]
    #[ORM\OneToOne(targetEntity: CatalogItem::class, inversedBy: 'catalogItemImage')]
    #[ORM\JoinColumn(name: 'catalog_item_id', referencedColumnName: 'catalog_item_id', nullable: false, onDelete: 'CASCADE')]
    private CatalogItem $catalogItem;

    #[ORM\Column(type: Types::BINARY)]
    private string $body;

    #[ORM\Column(length: 64)]
    private string $contentType;

    public function __construct(CatalogItem $catalogItem, Image $image)
    {
        $this->catalogItem = $catalogItem;
        $this->rewrite($image);
    }

    public function rewrite(Image $image): void
    {
        $this->body = $image->getBody();
        $this->contentType = $image->getMimeType();
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function getContentType(): string
    {
        return $this->contentType;
    }

    public function toImage(string $fileName): Image
    {
        return new Image($fileName, $this->body, $this->contentType);
    }
}

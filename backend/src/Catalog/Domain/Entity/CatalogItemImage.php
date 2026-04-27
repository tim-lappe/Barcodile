<?php

declare(strict_types=1);

namespace App\Catalog\Domain\Entity;

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

    public function __construct(CatalogItem $catalogItem, string $body, string $contentType)
    {
        $this->catalogItem = $catalogItem;
        $this->body = $body;
        $this->contentType = $contentType;
    }

    public function rewrite(string $body, string $contentType): void
    {
        $this->body = $body;
        $this->contentType = $contentType;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function getContentType(): string
    {
        return $this->contentType;
    }
}

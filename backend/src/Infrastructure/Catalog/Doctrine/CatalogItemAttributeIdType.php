<?php

declare(strict_types=1);

namespace App\Infrastructure\Catalog\Doctrine;

use App\Domain\Catalog\Entity\CatalogItemAttributeId;
use App\Infrastructure\Shared\Doctrine\Type\AbstractUuidIdDoctrineType;

final class CatalogItemAttributeIdType extends AbstractUuidIdDoctrineType
{
    public const NAME = 'catalog_item_attribute_id';

    public function getName(): string
    {
        return self::NAME;
    }

    protected function getIdClass(): string
    {
        return CatalogItemAttributeId::class;
    }
}

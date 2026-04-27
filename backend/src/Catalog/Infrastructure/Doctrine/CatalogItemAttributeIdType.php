<?php

declare(strict_types=1);

namespace App\Catalog\Infrastructure\Doctrine;

use App\SharedKernel\Domain\Id\CatalogItemAttributeId;
use App\SharedKernel\Infrastructure\Doctrine\Type\AbstractUuidIdDoctrineType;

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

<?php

declare(strict_types=1);

namespace App\Infrastructure\Catalog\Doctrine;

use App\Domain\Catalog\Entity\CatalogItemId;
use App\Infrastructure\Shared\Doctrine\Type\AbstractUuidIdDoctrineType;

final class CatalogItemIdType extends AbstractUuidIdDoctrineType
{
    public const NAME = 'catalog_item_id';

    public function getName(): string
    {
        return self::NAME;
    }

    protected function getIdClass(): string
    {
        return CatalogItemId::class;
    }
}

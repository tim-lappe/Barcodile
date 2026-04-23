<?php

declare(strict_types=1);

namespace App\Infrastructure\Catalog\Doctrine;

use App\Domain\Shared\Id\CatalogItemId;
use App\Infrastructure\Shared\Doctrine\Type\AbstractUuidIdDoctrineType;

final class CatalogItemIdType extends AbstractUuidIdDoctrineType
{
    public const NAME = CatalogItemId::DOCTRINE_TYPE_NAME;

    public function getName(): string
    {
        return CatalogItemId::DOCTRINE_TYPE_NAME;
    }

    protected function getIdClass(): string
    {
        return CatalogItemId::class;
    }
}

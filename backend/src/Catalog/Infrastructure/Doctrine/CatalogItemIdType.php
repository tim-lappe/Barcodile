<?php

declare(strict_types=1);

namespace App\Catalog\Infrastructure\Doctrine;

use App\SharedKernel\Domain\Id\CatalogItemId;
use App\SharedKernel\Infrastructure\Doctrine\Type\AbstractUuidIdDoctrineType;

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

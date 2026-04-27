<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\Doctrine;

use App\SharedKernel\Domain\Id\InventoryItemId;
use App\SharedKernel\Infrastructure\Doctrine\Type\AbstractUuidIdDoctrineType;

final class InventoryItemIdType extends AbstractUuidIdDoctrineType
{
    public const NAME = 'inventory_item_id';

    public function getName(): string
    {
        return self::NAME;
    }

    protected function getIdClass(): string
    {
        return InventoryItemId::class;
    }
}

<?php

declare(strict_types=1);

namespace App\Infrastructure\Inventory\Doctrine;

use App\Domain\Shared\Id\InventoryItemId;
use App\Infrastructure\Shared\Doctrine\Type\AbstractUuidIdDoctrineType;

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

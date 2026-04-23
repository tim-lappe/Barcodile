<?php

declare(strict_types=1);

namespace App\Infrastructure\Cart\Doctrine;

use App\Domain\Shared\Id\ShoppingCartLineId;
use App\Infrastructure\Shared\Doctrine\Type\AbstractUuidIdDoctrineType;

final class ShoppingCartLineIdType extends AbstractUuidIdDoctrineType
{
    public const NAME = 'shopping_cart_line_id';

    public function getName(): string
    {
        return self::NAME;
    }

    protected function getIdClass(): string
    {
        return ShoppingCartLineId::class;
    }
}

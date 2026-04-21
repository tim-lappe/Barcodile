<?php

declare(strict_types=1);

namespace App\Infrastructure\Cart\Doctrine;

use App\Domain\Cart\Entity\ShoppingCartId;
use App\Infrastructure\Shared\Doctrine\Type\AbstractUuidIdDoctrineType;

final class ShoppingCartIdType extends AbstractUuidIdDoctrineType
{
    public const NAME = 'shopping_cart_id';

    public function getName(): string
    {
        return self::NAME;
    }

    protected function getIdClass(): string
    {
        return ShoppingCartId::class;
    }
}

<?php

declare(strict_types=1);

namespace App\Cart\Infrastructure\Doctrine;

use App\SharedKernel\Domain\Id\ShoppingCartLineId;
use App\SharedKernel\Infrastructure\Doctrine\Type\AbstractUuidIdDoctrineType;

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
